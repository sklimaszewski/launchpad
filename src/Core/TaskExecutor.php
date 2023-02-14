<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use Novactive\Collection\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Launchpad\Core\Client\DockerCompose as DockerComposeClient;

class TaskExecutor
{
    /**
     * @var DockerComposeClient
     */
    protected $dockerComposeClient;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * @var Collection
     */
    protected $recipes;

    /**
     * @var array Docker environment variables
     */
    protected $dockerEnvVars;

    public function __construct(
        DockerComposeClient $dockerComposeClient,
        ProjectConfiguration $configuration,
        Collection $recipes,
        array $dockerEnvVars = []
    ) {
        $this->dockerComposeClient = $dockerComposeClient;
        $this->projectConfiguration = $configuration;
        $this->recipes = $recipes;
        $this->dockerEnvVars = $dockerEnvVars;
    }

    protected function checkRecipeAvailability(string $recipe): void
    {
        if (!$this->recipes->contains($recipe)) {
            throw new RuntimeException("Recipe {$recipe} is not available.");
        }
    }

    /**
     * @return Process[]
     */
    public function composerInstall(): array
    {
        $recipe = 'composer_install';
        $this->checkRecipeAvailability($recipe);

        $processes = [];
        // composer install
        $processes[] = $this->execute("{$recipe}.bash");

        // Composer Configuration
        $httpBasics = $this->projectConfiguration->get('composer.http_basic');
        if (\is_array($httpBasics)) {
            foreach ($httpBasics as $auth) {
                if (!isset($auth['host'], $auth['login'], $auth['password'])) {
                    continue;
                }
                $processes[] = $this->globalExecute(
                    '/usr/local/bin/composer config --global'.
                    " http-basic.{$auth['host']} {$auth['login']} {$auth['password']}"
                );
            }
        }

        $tokens = $this->projectConfiguration->get('composer.token');
        if (\is_array($tokens)) {
            foreach ($tokens as $auth) {
                if (!isset($auth['host'], $auth['value'])) {
                    continue;
                }
                $processes[] = $this->globalExecute(
                    '/usr/local/bin/composer config --global'." github-oauth.{$auth['host']} {$auth['value']}"
                );
            }
        }

        return $processes;
    }

    public function symfonyInstall(string $version, string $repository, string $initialData): Process
    {
        $recipe = 'sf_install';
        $this->checkRecipeAvailability($recipe);

        return $this->execute("{$recipe}.bash {$repository} {$version} {$initialData}");
    }

    public function symfonyCreate(): Process
    {
        $recipe = 'sf_create';
        $this->checkRecipeAvailability($recipe);
        $projectFolder = $this->projectConfiguration->get('provisioning.project_folder_name');

        return $this->execute("{$recipe}.bash {$projectFolder}");
    }

    public function dumpData(): Process
    {
        $recipe = 'create_dump';
        $this->checkRecipeAvailability($recipe);

        $args = [];
        foreach ($this->projectConfiguration->get('docker.storage_dirs') as $name => $path) {
            $args[] = $name.'='.trim($path, '/');
        }

        return $this->execute("{$recipe}.bash ".implode(' ', $args));
    }

    public function importData(): Process
    {
        $recipe = 'import_dump';
        $this->checkRecipeAvailability($recipe);

        $args = [];
        foreach ($this->projectConfiguration->get('docker.storage_dirs') as $name => $path) {
            $args[] = $name.'='.trim($path, '/');
        }

        return $this->execute("{$recipe}.bash ".implode(' ', $args));
    }

    public function runSymfonyCommand(string $arguments): Process
    {
        $consolePath = $this->dockerComposeClient->isLegacySymfony() ? 'app/console' : 'bin/console';
        $projectFolder = $this->projectConfiguration->get('provisioning.project_folder_name');

        return $this->execute("{$projectFolder}/{$consolePath} {$arguments}");
    }

    public function runComposerCommand(string $arguments): Process
    {
        $projectFolder = $this->projectConfiguration->get('provisioning.project_folder_name');

        return $this->globalExecute(
            '/usr/local/bin/composer --working-dir='.$this->dockerComposeClient->getProjectPathContainer().'/' . $projectFolder . ' '.
            $arguments
        );
    }

    protected function execute(string $command, string $user = 'www-data', ?string $service = null)
    {
        if (!$service) {
            $service = $this->projectConfiguration->get('docker.main_container');
        }

        $command = $this->dockerComposeClient->getProjectPathContainer().'/'.$command;

        return $this->globalExecute($command, $user, $service);
    }

    protected function globalExecute(string $command, string $user = 'www-data', ?string $service = null)
    {
        if (!$service) {
            $service = $this->projectConfiguration->get('docker.main_container');
        }

        $args = ['--user', $user];

        foreach ($this->dockerEnvVars as $envVar) {
            $args = array_merge($args, ['--env', $envVar]);
        }

        return $this->dockerComposeClient->exec($command, $args, $service);
    }
}
