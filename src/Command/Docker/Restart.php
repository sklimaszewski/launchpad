<?php

declare(strict_types=1);

namespace Symfony\Launchpad\Command\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\DockerComposeCommand;

/**
 * Class Restart.
 */
final class Restart extends DockerComposeCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:restart')->setDescription('Restart all the services (or just one).');
        $this->addArgument('service', InputArgument::OPTIONAL, 'Service to restart', '');
        $this->setAliases(['restart']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerComposeClient->restart($input->getArgument('service'));

        return DockerComposeCommand::SUCCESS;
    }
}
