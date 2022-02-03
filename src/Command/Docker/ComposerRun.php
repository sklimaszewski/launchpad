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

class ComposerRun extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:comprun')->setDescription('Run Composer command in the symfony container.');
        $this->setAliases(['comprun']);
        $this->addArgument(
            'compcommand',
            InputArgument::IS_ARRAY,
            'Composer Command to run in. Use "" to pass options.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allArguments = $input->getArgument('compcommand');
        $options = '';
        $this->taskExecutor->runComposerCommand(implode(' ', $allArguments)." {$options}");

        return DockerComposeCommand::SUCCESS;
    }
}
