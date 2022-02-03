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

final class DumpData extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:dumpdata')->setDescription('Dump Database and Storage.');
        $this->setAliases(['dumpdata']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->taskExecutor->dumpData();

        return DockerComposeCommand::SUCCESS;
    }
}
