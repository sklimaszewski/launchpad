<?php

namespace Symfony\Launchpad\Core\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;
use Symfony\Launchpad\Core\ProcessRunner;

class Docker
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
            'registry-name' => null,
            'registry-username' => null,
            'registry-password' => null,
            'provisioning-folder-name' => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('registry-name', ['null', 'string']);
        $resolver->setAllowedTypes('registry-username', ['null', 'string']);
        $resolver->setAllowedTypes('registry-password', ['null', 'string']);
        $resolver->setAllowedTypes('provisioning-folder-name', 'string');
        $this->options = $resolver->resolve($options);
        $this->runner = $runner;
    }

    public function build(string $container, string $tag = 'latest')
    {
        $args = [
            '--network host',
            '--build-arg BUILDKIT_INLINE_CACHE=1',
            '--tag ' . $this->options['registry-name'] . '/' . $container . ':' . $tag,
            '--file ' . $this->options['provisioning-folder-name'] . '/dev/' . $container . '/Dockerfile',
            '.',
        ];

        return $this->perform('build', $args);
    }

    public function login()
    {
        $args = [
            $this->getRegistryHost(),
            '--username ' . $this->options['registry-username'],
            '--password-stdin',
        ];
        return $this->perform('login', $args, $this->options['registry-password']);
    }

    public function push(string $container, string $tag = 'latest')
    {
        $args = [
            $this->options['registry-name'] . '/' . $container . ':' . $tag,
        ];

        return $this->perform('push', $args);
    }

    protected function getDockerEnvVariables(): array
    {
        return [
            'DOCKER_BUILDKIT' => 1,
        ];
    }

    protected function getRegistryHost(): string
    {
        return parse_url('//' . $this->options['registry-name'], PHP_URL_HOST);
    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, array $args = [], ?string $stdin = null, bool $dryRun = false)
    {
        $stringArgs = implode(' ', $args);
        $command = "docker";

        $fullCommand = trim("{$command} {$action} {$stringArgs}");
        if ($stdin) {
            $fullCommand = trim('echo "' . $stdin . '" | ' . $fullCommand);
        }

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, $this->getDockerEnvVariables());
        }

        return $fullCommand;
    }
}