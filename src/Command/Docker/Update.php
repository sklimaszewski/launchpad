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

final class Update extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:update')->setDescription('Update to last images.');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Image service to update.', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerComposeClient->pull(['--ignore-pull-failures'], $input->getArgument('service'));
        $this->dockerComposeClient->build([], $input->getArgument('service'));
        $this->dockerComposeClient->up(['-d'], $input->getArgument('service'));
        $this->taskExecutor->composerInstall();

        return DockerComposeCommand::SUCCESS;
    }
}
