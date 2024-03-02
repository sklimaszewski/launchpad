<?php

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Launchpad\Core\Client\Kubectl;

abstract class KubernetesCommand extends Command
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Kubectl
     */
    protected $kubectlClient;

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('kubeconfig', 'k', InputOption::VALUE_REQUIRED, 'Custom kubeconfig file.');
        $this->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Kubernetes namespace');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

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

        $options = [
            'kubeconfig-file' => $kubeConfigPath,
            'namespace' => $namespace,
        ];

        $this->kubectlClient = new Kubectl($options, new ProcessRunner());
    }
}
