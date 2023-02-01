<?php

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\Client\Docker;

abstract class DockerCommand extends Command
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Docker
     */
    protected $dockerClient;

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $dockerOptions = [
            'registry-name' => $this->projectConfiguration->get('kubernetes.registry.name'),
            'registry-username' => $this->projectConfiguration->get('kubernetes.registry.username'),
            'registry-password' => $this->projectConfiguration->get('kubernetes.registry.password'),
            'provisioning-folder-name' => $this->projectConfiguration->get('provisioning.folder_name'),
        ];

        $this->dockerClient = new Docker($dockerOptions, new ProcessRunner());
    }
}
