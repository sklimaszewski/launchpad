<?php

namespace Symfony\Launchpad\Core\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;
use Symfony\Launchpad\Core\ProcessRunner;

class Kubectl
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ProcessRunner
     */
    protected $runner;

    public function __construct(array $options, ProcessRunner $runner)
    {
        $resolver = new OptionsResolver();
        $defaults = [
            'kubeconfig-file' => null,
            'namespace' => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('kubeconfig-file', 'string');
        $resolver->setAllowedTypes('namespace', 'string');
        $this->options = $resolver->resolve($options);
        $this->runner = $runner;
    }

    public function exec(string $shell, string $pod, array $args = [])
    {
        $args[] = '-it';

        return $this->perform('exec', $pod, $args, $shell);
    }

    public function portForward(string $pod, string $port)
    {
        return $this->perform('port-forward', $pod, [], $port);
    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, string $pod, array $options = [], string $argument = '', bool $dryRun = false)
    {
        $args = [
            "--namespace {$this->getNamespace()}",
            "--kubeconfig {$this->getKubeConfigFile()}",
        ];

        $stringOptions = implode(' ', $args);
        $pod = "$(kubectl get {$stringOptions} pods --no-headers -o custom-columns=:metadata.name | grep \"$pod\")";

        $stringOptions = implode(' ', array_merge($args, $options));
        $fullCommand = trim("kubectl {$action} {$stringOptions} {$pod} -- {$argument} ");

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, []);
        }

        return $fullCommand;
    }

    protected function getKubeConfigFile(): string
    {
        return $this->options['kubeconfig-file'];
    }

    protected function getNamespace(): string
    {
        return $this->options['namespace'];
    }
}