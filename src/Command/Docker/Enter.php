<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Command\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\DockerComposeCommand;

final class Enter extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:enter')->setDescription('Enter in a container.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to enter in');
        $this->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User with who to enter in', 'www-data');
        $this->addArgument('shell', InputArgument::OPTIONAL, 'Command to enter in', '/bin/bash');
        $this->setAliases(['enter', 'docker:exec', 'exec']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $input->getArgument('service');
        if (!$service) {
            $service = $this->projectConfiguration->get('docker.main_container');
        }

        $this->dockerComposeClient->exec(
            $input->getArgument('shell'),
            [
                '--user', $input->getOption('user'),
            ],
            $service
        );

        return DockerComposeCommand::SUCCESS;
    }
}
