<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Launchpad\Core\Client\DockerCompose;

abstract class DockerComposeCommand extends Command
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var DockerCompose
     */
    protected $dockerComposeClient;

    /**
     * @var TaskExecutor
     */
    protected $taskExecutor;

    protected function configure(): void
    {
        $this->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev');
        $this->addOption(
            'docker-env',
            'd',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Docker environment variables',
            []
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->environment = $input->getOption('env');
        $this->projectConfiguration->setEnvironment($this->environment);

        $fs = new Filesystem();
        $currentPwd = $this->projectPath;
        $provisioningFolder = $this->projectConfiguration->get('provisioning.folder_name');
        $dockerComposeFileName = $this->projectConfiguration->get('docker.compose_filename');
        $dockerComposeFileFolder = NovaCollection([$currentPwd, $provisioningFolder, $this->environment])->implode(
            '/'
        );

        if (!$fs->exists($dockerComposeFileFolder."/{$dockerComposeFileName}")) {
            throw new RuntimeException("There is no {$dockerComposeFileName} in {$dockerComposeFileFolder}");
        }
        $options = [
            'compose-file' => $dockerComposeFileFolder."/{$dockerComposeFileName}",
            'network-name' => $this->projectConfiguration->get('docker.network_name'),
            'network-prefix-port' => $this->projectConfiguration->get('docker.network_prefix_port'),
            'host-machine-mapping' => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'project-path' => $this->projectPath,
            'provisioning-folder-name' => $provisioningFolder,
            'composer-cache-dir' => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
            'env-variables' => $this->projectConfiguration->get('docker.variables'),
        ];

        $this->dockerComposeClient = new DockerCompose($options, new ProcessRunner());
        $this->taskExecutor = new TaskExecutor(
            $this->dockerComposeClient,
            $this->projectConfiguration,
            $this->requiredRecipes,
            $input->getOption('docker-env')
        );
    }
}
