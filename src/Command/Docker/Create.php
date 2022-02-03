<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Command\Docker;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\DockerComposeCommand;
use Symfony\Launchpad\Core\ProjectStatusDumper;

final class Create extends DockerComposeCommand
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
        $this->setName('docker:create')->setDescription('Create all the services.');
        $this->setAliases(['create']);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setDockerClient($this->dockerComposeClient);
        $this->projectStatusDumper->setIo($this->io);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerComposeClient->build(['--no-cache']);
        $this->dockerComposeClient->up(['-d']);

        $this->taskExecutor->composerInstall();
        $this->taskExecutor->symfonyCreate();
        $this->taskExecutor->importData();

        $this->projectStatusDumper->dump('ncsi');

        return DockerComposeCommand::SUCCESS;
    }
}
