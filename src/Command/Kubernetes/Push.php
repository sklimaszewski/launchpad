<?php

namespace Symfony\Launchpad\Command\Kubernetes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\DockerCommand;

final class Push extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('k8s:push')->setDescription('Push new image of a given container to the registry.');
        $this->addArgument('container', InputArgument::REQUIRED, 'Container name to push its image');
        $this->addOption('tag', 't', InputOption::VALUE_OPTIONAL, 'Image tag', 'latest');
        $this->setAliases(['push']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerClient->login();
        $this->dockerClient->push(
            $input->getArgument('container'),
            $input->getOption('tag')
        );

        return DockerCommand::SUCCESS;
    }
}
