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

final class SymfonyRun extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:sfrun')->setDescription('Run a Symfony command in the symfony container.');
        $this->setAliases(['sfrun']);
        $this->addArgument('sfcommand', InputArgument::IS_ARRAY, 'Symfony Command to run in. Use "" to pass options.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allArguments = $input->getArgument('sfcommand');
        $options = '';
        $this->taskExecutor->runSymfonyCommand(implode(' ', $allArguments)." {$options}");

        return DockerComposeCommand::SUCCESS;
    }
}
