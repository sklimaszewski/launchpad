<?php

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Launchpad\Core\Client\Helm;

abstract class HelmCommand extends Command
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Helm
     */
    protected $helmClient;

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev');
        $this->addOption('kubeconfig', 'k', InputOption::VALUE_REQUIRED, 'Custom kubeconfig file.');
        $this->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Kubernetes namespace');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->environment = $input->getOption('env');
        $this->projectConfiguration->setEnvironment($this->environment);

        $kubeConfigPath = $this->projectConfiguration->get('kubernetes.kubeconfig');
        if ($input->getOption('kubeconfig')) {
            $kubeConfigPath = $input->getOption('kubeconfig');
        }

        // Home directory fix
        if ($kubeConfigPath) {
            $kubeConfigPath = str_replace('~/', getenv('HOME').'/', $kubeConfigPath);
        }

        $namespace = $this->projectConfiguration->get('kubernetes.namespace');
        if ($input->getOption('namespace')) {
            $namespace = $input->getOption('namespace');
        }

        $helmChartPath = $this->projectConfiguration->getKubernetesHelmPath();

        $options = [
            'kubeconfig-file' => $kubeConfigPath,
            'registry-name' => $this->projectConfiguration->get('kubernetes.registry.name'),
            'registry-username' => $this->projectConfiguration->get('kubernetes.registry.username'),
            'registry-password' => $this->projectConfiguration->get('kubernetes.registry.password'),
            'helm-chart-path' => $helmChartPath,
            'namespace' => $namespace,
        ];

        $this->helmClient = new Helm($options, new ProcessRunner());
    }
}
