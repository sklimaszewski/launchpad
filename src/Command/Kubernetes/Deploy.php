<?php

namespace Symfony\Launchpad\Command\Kubernetes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\HelmCommand;

final class Deploy extends HelmCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('k8s:deploy')->setDescription('Deploy application to Kubernetes using Helm Chart.');
        $this->addOption('tag', 't', InputOption::VALUE_REQUIRED, 'Image tag', 'latest');
        $this->addArgument('name', InputArgument::OPTIONAL, 'Application name');
        $this->setAliases(['deploy']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->helmClient->dependencyUpdate();

        $this->helmClient->deploy(
            $input->getOption('tag'),
            $input->getArgument('name')
        );

        return HelmCommand::SUCCESS;
    }
}
