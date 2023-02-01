<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Command\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Launchpad\Console\Application;
use Symfony\Launchpad\Core\Client\DockerCompose as DockerComposeClient;
use Symfony\Launchpad\Core\Command;
use Symfony\Launchpad\Core\DockerCompose;
use Symfony\Launchpad\Core\ProcessRunner;
use Symfony\Launchpad\Core\ProjectStatusDumper;
use Symfony\Launchpad\Core\ProjectWizard;
use Symfony\Launchpad\Core\TaskExecutor;

class Initialize extends Command
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    public function __construct(ProjectStatusDumper $projectStatusDumper)
    {
        parent::__construct();
        $this->projectStatusDumper = $projectStatusDumper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:initialize')->setDescription('Initialize the project and all the services.');
        $this->setAliases(['docker:init', 'initialize', 'init']);
        $this->addArgument('repository', InputArgument::OPTIONAL, 'Symfony Repository', 'symfony/website-skeleton');
        $this->addArgument('version', InputArgument::OPTIONAL, 'Symfony Version', '5.4');
        $this->addArgument(
            'initialdata',
            InputArgument::OPTIONAL,
            'Installer: If available uses "composer run-script <initialdata>", if not uses symfony:install command',
            'symfony-install'
        );
    }

    private function getSymfonyMajorVersion(InputInterface $input): int
    {
        $normalizedVersion = trim($input->getArgument('version'), 'v');

        return (int) str_replace(['^', '~'], '', $normalizedVersion);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        // Get the Payload docker-compose.yml
        $compose = new DockerCompose("{$this->getPayloadDir()}/docker/dev/docker-compose.yml");
        $wizard = new ProjectWizard($this->io, $this->projectConfiguration);

        // Ask the questions
        [
            $networkName,
            $networkPort,
            $selectedServices,
            $provisioningName,
            $composeFileName,
            $kubernetesConfig
        ] = $wizard(
            $compose
        );

        $compose->filterServices($selectedServices);

        // start the scafolding of the Payload
        $provisioningFolder = "{$this->projectPath}/{$provisioningName}";
        $fs->mkdir("{$provisioningFolder}/dev");
        $fs->mirror("{$this->getPayloadDir()}/docker/dev", "{$provisioningFolder}/dev");
        $fs->chmod(
            [
                "{$provisioningFolder}/dev/nginx/entrypoint.bash",
                "{$provisioningFolder}/dev/symfony/entrypoint.bash",
            ],
            0755
        );

        // kubernetes
        if ($kubernetesConfig) {
            $kubernetesFolderFolder = "{$this->projectPath}/{$kubernetesConfig['folder_name']}";
            $fs->mkdir($kubernetesFolderFolder);
            $fs->mirror("{$this->getPayloadDir()}/kubernetes", $kubernetesFolderFolder);
        }

        unset($selectedServices);

        $majorVersion = $this->getSymfonyMajorVersion($input);

        // Symfony 2.x specific versions
        if (2 === $majorVersion) {
            // PHP 7.2
            $symfonyDockerFilePath = "{$provisioningFolder}/dev/engine/Dockerfile";
            $symfonyDockerFileContent = file_get_contents($symfonyDockerFilePath);
            file_put_contents(
                $symfonyDockerFilePath,
                str_replace(
                    'php-symfony:7.4-fpm-mysql',
                    'php-symfony:7.2-fpm-mysql',
                    $symfonyDockerFileContent
                )
            );
        }

        // Symfony 3.x specific versions
        if (3 === $majorVersion) {
            // PHP 7.3
            $symfonyDockerFilePath = "{$provisioningFolder}/dev/engine/Dockerfile";
            $symfonyDockerFileContent = file_get_contents($symfonyDockerFilePath);
            file_put_contents(
                $symfonyDockerFilePath,
                str_replace(
                    'php-symfony:7.4-fpm-mysql',
                    'php-symfony:7.3-fpm-mysql',
                    $symfonyDockerFileContent
                )
            );
        }

        // Symfony <= 3 has another vhost
        if ($majorVersion <= 3) {
            rename("{$provisioningFolder}/dev/nginx/nginx_v3.conf", "{$provisioningFolder}/dev/nginx/nginx.conf");
        } else {
            // no need for v3 nginx config
            unlink("{$provisioningFolder}/dev/nginx/nginx_v3.conf");
        }

        // Clean the Compose File
        $compose->removeUselessEnvironmentsVariables($majorVersion);

        // Get the Payload README.md & .dockerignore
        $fs->copy("{$this->getPayloadDir()}/docker/README.md", "{$provisioningFolder}/README.md");
        $fs->copy("{$this->getPayloadDir()}/docker/.dockerignore", "{$provisioningFolder}/.dockerignore");

        // create the local configurations
        $localConfigurations = [
            'provisioning.folder_name' => $provisioningName,
            'docker.compose_filename' => $composeFileName,
            'docker.network_name' => $networkName,
            'docker.network_prefix_port' => $networkPort,
        ];
        if ($kubernetesConfig) {
            $localConfigurations = array_merge($localConfigurations, [
                'kubernetes.folder_name' => $kubernetesConfig['folder_name'],
                'kubernetes.kubeconfig' => $kubernetesConfig['kubeconfig'],
                'kubernetes.registry.name' => $kubernetesConfig['registry']['name'],
                'kubernetes.registry.username' => $kubernetesConfig['registry']['username'],
                'kubernetes.registry.password' => $kubernetesConfig['registry']['password'],
                'kubernetes.namespace' => $kubernetesConfig['namespace'],
            ]);
        }

        $this->projectConfiguration->setMultiLocal($localConfigurations);

        // Create the docker Client
        $options = [
            'compose-file' => "{$provisioningFolder}/dev/{$composeFileName}",
            'network-name' => $networkName,
            'network-prefix-port' => $networkPort,
            'project-path' => $this->projectPath,
            'provisioning-folder-name' => $provisioningName,
            'host-machine-mapping' => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'composer-cache-dir' => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
        ];
        $dockerClient = new DockerComposeClient($options, new ProcessRunner(), $this->optimizer);
        $this->projectStatusDumper->setDockerClient($dockerClient);

        // do the real work
        $this->innerInitialize(
            $dockerClient,
            $compose,
            "{$provisioningFolder}/dev/{$composeFileName}",
            $input
        );

        $this->projectConfiguration->setEnvironment('dev');
        $this->projectStatusDumper->dump('ncsi');

        return Command::SUCCESS;
    }

    protected function innerInitialize(
        DockerComposeClient $dockerClient,
        DockerCompose $compose,
        string $composeFilePath,
        InputInterface $input
    ): void {
        $tempCompose = clone $compose;
        $tempCompose->cleanForInitialize();
        // dump the temporary DockerCompose.yml without the mount and env vars in the provisioning folder
        $tempCompose->dump($composeFilePath);
        unset($tempCompose);

        // Do the first pass to get Symfony and related files
        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);

        $executor = new TaskExecutor($dockerClient, $this->projectConfiguration, $this->requiredRecipes);
        $executor->composerInstall();

        $repository = $input->getArgument('repository');
        $initialdata = $input->getArgument('initialdata');

        $normalizedVersion = trim($input->getArgument('version'), 'v');

        $executor->symfonyInstall($normalizedVersion, $repository, $initialdata);

        $compose->dump($composeFilePath);

        $dockerClient->up(['-d']);
        $executor->composerInstall();
    }
}
