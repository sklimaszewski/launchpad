<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core\Client;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;
use Symfony\Launchpad\Core\ProcessRunner;

class DockerCompose
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ProcessRunner
     */
    protected $runner;

    public function __construct(array $options, ProcessRunner $runner)
    {
        $resolver = new OptionsResolver();
        $defaults = [
            'compose-file' => null,
            'network-name' => null,
            'network-prefix-port' => null,
            'project-path' => null,
            'project-path-container' => '/var/www/html/project',
            'host-machine-mapping' => null,
            'provisioning-folder-name' => null,
            'composer-cache-dir' => null,
            'env-variables' => [],
            'context' => null,
            'project-folder-name' => 'symfony',
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('compose-file', 'string');
        $resolver->setAllowedTypes('project-path', 'string');
        $resolver->setAllowedTypes('project-path-container', 'string');
        $resolver->setAllowedTypes('network-name', 'string');
        $resolver->setAllowedTypes('composer-cache-dir', ['null', 'string']);
        $resolver->setAllowedTypes('provisioning-folder-name', 'string');
        $resolver->setAllowedTypes('network-prefix-port', 'int');
        $resolver->setAllowedTypes('host-machine-mapping', ['null', 'string']);
        $resolver->setAllowedTypes('env-variables', 'array');
        $resolver->setAllowedTypes('context', ['null', 'string']);
        $resolver->setAllowedTypes('project-folder-name', 'string');
        $this->options = $resolver->resolve($options);
        $this->runner = $runner;
    }

    protected function getComposeFileName(): string
    {
        return $this->options['compose-file'];
    }

    protected function getNetworkName(): string
    {
        return $this->options['network-name'];
    }

    protected function getNetworkPrefixPort(): int
    {
        return $this->options['network-prefix-port'];
    }

    protected function getProjectPath(): string
    {
        return $this->options['project-path'];
    }

    protected function getProjectFolderName(): string
    {
        return $this->options['project-folder-name'];
    }

    protected function getContext(): ?string
    {
        return $this->options['context'];
    }

    public function isLegacySymfony(): bool
    {
        $fs = new Filesystem();
        return $fs->exists("{$this->getProjectPath()}/{$this->getProjectFolderName()}/app/console");
    }

    public function getProjectPathContainer(): string
    {
        return $this->options['project-path-container'];
    }

    protected function getProvisioningFolderName(): string
    {
        return $this->options['provisioning-folder-name'];
    }

    protected function getHostExportedPath(): string
    {
        return explode(':', $this->options['host-machine-mapping'])[0];
    }

    protected function getMachineMountPath(): string
    {
        return explode(':', $this->options['host-machine-mapping'])[1];
    }

    public function start(string $service = '')
    {
        return $this->perform('start', $service);
    }

    public function restart(string $service = '')
    {
        return $this->perform('restart', $service);
    }

    public function build(array $args = [], string $service = '')
    {
        return $this->perform('build', $service, $args);
    }

    public function up(array $args = [], string $service = '')
    {
        return $this->perform('up', $service, $args);
    }

    public function remove(array $args = [], string $service = '')
    {
        return $this->perform('rm', $service, $args);
    }

    public function stop(string $service = '')
    {
        return $this->perform('stop', $service);
    }

    public function down(array $args = [])
    {
        return $this->perform('down', '', $args);
    }

    public function ps(array $args = [])
    {
        return $this->perform('ps', '', $args);
    }

    public function logs(array $args = [], string $service = '')
    {
        return $this->perform('logs', $service, $args);
    }

    public function pull(array $args = [], string $service = '')
    {
        return $this->perform('pull', $service, $args);
    }

    public function exec(string $command, array $args, string $service)
    {
        $args[] = $service;
        $args[] = $command;

        // Disable TTY if is not supported by the host (CI)
        if (!$this->runner->hasTty()) {
            array_unshift($args, '-T');
        }

        return $this->perform('exec', '', $args);
    }

    public function getComposeEnvVariables(): array
    {
        $projectComposePath = '../../';
        if (null != $this->options['host-machine-mapping']) {
            $projectComposePath = $this->getMachineMountPath().
                                  str_replace($this->getHostExportedPath(), '', $this->getProjectPath());
        }
        $composerCacheDir = getenv('HOME').'/.composer/cache';
        if (null != $this->options['composer-cache-dir']) {
            $composerCacheDir = $this->options['composer-cache-dir'];
        }

        $variables = [
            'PROJECTNETWORKNAME' => $this->getNetworkName(),
            'PROJECTPORTPREFIX' => $this->getNetworkPrefixPort(),
            'PROJECTCOMPOSEPATH' => MacOSPatherize($projectComposePath),
            'PROVISIONINGFOLDERNAME' => $this->getProvisioningFolderName(),
            'HOST_COMPOSER_CACHE_DIR' => MacOSPatherize($composerCacheDir),
            'DEV_UID' => getmyuid(),
            'DEV_GID' => getmygid(),
            // In container composer cache directory - (will be mapped to host:composer-cache-dir)
            'COMPOSER_CACHE_DIR' => '/var/www/composer_cache',
            // where to mount the project root directory in the container - (will be mapped to host:project-path)
            'PROJECTMAPPINGFOLDER' => $this->getProjectPathContainer(),
            // pass the DOCKER native vars for compose
            'DOCKER_HOST' => getenv('DOCKER_HOST'),
            'DOCKER_CERT_PATH' => getenv('DOCKER_CERT_PATH'),
            'DOCKER_TLS_VERIFY' => getenv('DOCKER_TLS_VERIFY'),
            'PATH' => getenv('PATH'),
            'XDEBUG_ENABLED' => false === getenv('XDEBUG_ENABLED') ? '0' : '1',
        ];

        foreach ($this->options['env-variables'] as $variable => $value) {
            $variables[strtoupper($variable)] = $value;
        }

        return $variables;

    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, string $service = '', array $args = [], bool $dryRun = false)
    {
        $stringArgs = implode(' ', $args);
        if ($this->getContext()) {
            $command = "docker --context={$this->getContext()} compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";
        } else {
            $command = "docker compose -p {$this->getNetworkName()} -f {$this->getComposeFileName()}";
        }

        $fs = new Filesystem();

        if (SF_ON_OSX) {
            $osxExtension = str_replace('.yml', '.osx.yml', $this->getComposeFileName());
            if ($fs->exists($osxExtension)) {
                $command .= " -f {$osxExtension}";
            }
        }

        if (SF_ON_ARM64) {
            $arm64Extension = str_replace('.yml', '.arm64.yml', $this->getComposeFileName());
            $fs = new Filesystem();
            if ($fs->exists($arm64Extension)) {
                $command .= " -f {$arm64Extension}";
            }
        }

        $fullCommand = trim("{$command} {$action} {$stringArgs} {$service} ");

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, $this->getComposeEnvVariables());
        }

        return $fullCommand;
    }

    public function getComposeCommand(): string
    {
        $vars = NovaCollection($this->getComposeEnvVariables());

        $prefix = $vars->map(
            function ($value, $key) {
                return $key.'='.$value;
            }
        )->implode(' ');

        return "{$prefix} ".$this->perform('', '', [], true);
    }
}
