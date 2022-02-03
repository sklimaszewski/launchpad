<?php

namespace Symfony\Launchpad\Command\Kubernetes;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Launchpad\Core\KubernetesCommand;

final class Tunnel extends KubernetesCommand
{
    private const SERVICE_PORT_MAP = [
        'mongo' => 27017,
        'mongodb' => 27017,
        'solr' => 8983,
        'nginx' => 80,
        'varnish' => 80,
        'elastic' => 9200,
        'elasticsearch' => 9200,
        'mariadb' => 3306,
        'mysql' => 3306,
    ];

    protected function configure(): void
    {
        parent::configure();
        $this->setName('k8s:tunnel')->setDescription('Forward port to/from selected pod.');
        $this->addArgument('pod', InputArgument::REQUIRED, 'Pod to enter in');
        $this->addArgument('port', InputArgument::OPTIONAL, 'Command to enter in');
        $this->setAliases(['tunnel']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pod = $input->getArgument('pod');
        $port = $input->getArgument('port');

        if (!$port && isset(self::SERVICE_PORT_MAP[$pod])) {
            $port = self::SERVICE_PORT_MAP[$pod];
        }

        $this->kubectlClient->portForward($pod, $port);

        return KubernetesCommand::SUCCESS;
    }
}