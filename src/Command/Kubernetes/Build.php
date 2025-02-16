<?php

declare(strict_types=1);

namespace Symfony\Launchpad\Command\Kubernetes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\DockerCommand;

final class Build extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('k8s:build')->setDescription('Build new image for a given container.');
        $this->addArgument('container', InputArgument::REQUIRED, 'Container name to build image');
        $this->addOption(
            'tag',
            't',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Image tag',
            ['latest']
        );
        $this->addOption('cache-from', null, InputOption::VALUE_REQUIRED, 'Build image from cache');
        $this->addOption('architecture', 'a', InputOption::VALUE_REQUIRED, 'Specify build architecture (linux/amd64, linux/arm64)');
        $this->addOption('push', 'p', InputOption::VALUE_NONE, 'Build image from cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dockerClient->login();

        $this->dockerClient->build(
            $input->getArgument('container'),
            $input->getOption('tag'),
            $input->getOption('cache-from'),
            $input->getOption('architecture'),
            (bool) $input->getOption('push')
        );

        $this->dockerClient->logout();

        return DockerCommand::SUCCESS;
    }
}
