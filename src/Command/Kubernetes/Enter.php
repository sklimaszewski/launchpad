<?php

namespace Symfony\Launchpad\Command\Kubernetes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\KubernetesCommand;

final class Enter extends KubernetesCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('k8s:enter')->setDescription('Enter in a container.');
        $this->addOption(
            'container',
            null,
            InputOption::VALUE_OPTIONAL,
            'Container name. If omitted, the first container in the pod will be chosen.'
        );
        $this->addArgument('pod', InputArgument::REQUIRED, 'Pod to enter in');
        $this->addArgument('shell', InputArgument::OPTIONAL, 'Command to enter in', 'bash');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = [];
        if ($input->getOption('container')) {
            $args = [
                '--container', $input->getOption('container'),
            ];
        }

        $this->kubectlClient->exec(
            $input->getArgument('shell'),
            $input->getArgument('pod'),
            $args
        );

        return KubernetesCommand::SUCCESS;
    }
}
