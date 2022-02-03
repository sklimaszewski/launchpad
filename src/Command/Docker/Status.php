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
use Symfony\Launchpad\Core\DockerComposeCommand;
use Symfony\Launchpad\Core\ProjectStatusDumper;

final class Status extends DockerComposeCommand
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

    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:status')->setDescription('Obtaining the project information.');
        $this->setAliases(['docker:ps', 'docker:info', 'ps', 'info']);
        $this->addArgument(
            'options',
            InputArgument::OPTIONAL,
            'n: Docker Network, c: Docker Compose, s: Service Access',
            'ncsz'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setDockerClient($this->dockerComposeClient);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->projectStatusDumper->dump($input->getArgument('options'));

        return DockerComposeCommand::SUCCESS;
    }
}
