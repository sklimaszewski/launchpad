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

final class Build extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:build')->setDescription('Build all the services (or just one).');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to build', '');
        $this->setAliases(['build']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerComposeClient->build([], $input->getArgument('service'));
        $this->taskExecutor->composerInstall();

        return DockerComposeCommand::SUCCESS;
    }
}
