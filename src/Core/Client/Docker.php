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

    public function build(string $container, array $tags = ['latest'], ?string $cacheFrom = null, ?string $platform = null, bool $push)
    {
        $args = [];

        if ($cacheFrom) {
            $args[] = '--cache-from '.$this->options['registry-name'].'/'.$container.':'.$cacheFrom;
        }

        if ($platform) {
            $args[] = '--platform ' . $platform;
        }

        if ($push) {
            $args[] = '--push';
        }

        foreach ($tags as $tag) {
            $args[] = '--tag '.$this->options['registry-name'].'/'.$container.':'.$tag;
        }

        $args[] = '--file '.$this->options['provisioning-folder-name'].'/dev/'.$container.'/Dockerfile';
        $args[] = '.';

        return $this->perform('buildx build',$args);
    }

    public function login()
    {
        if ($this->getRegistryHost() && $this->options['registry-username'] && $this->options['registry-password']) {
            $args = [
                $this->getRegistryHost(),
                '--username ' . $this->options['registry-username'],
                '--password-stdin',
            ];

            return $this->perform('login', $args, $this->options['registry-password']);
        }
    }

    public function logout()
    {
        if ($this->getRegistryHost() && $this->options['registry-username'] && $this->options['registry-password']) {
            $args = [
                $this->getRegistryHost(),
            ];

            return $this->perform('logout', $args);
        }
    }

    public function push(string $container, string $tag = 'latest')
    {
        $args = [
            $this->options['registry-name'].'/'.$container.':'.$tag,
        ];

        return $this->perform('push', $args);
    }

    protected function getDockerEnvVariables(): array
    {
        return [
            'DOCKER_BUILDKIT' => 1,
        ];
    }

    protected function getRegistryHost()
    {
        if (!$this->options['registry-name']) {
            return null;
        }

        return parse_url('//'.$this->options['registry-name'], PHP_URL_HOST);
    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, array $args = [], ?string $stdin = null, bool $dryRun = false)
    {
        $stringArgs = implode(' ', $args);
        $command = 'docker';

        $fullCommand = trim("{$command} {$action} {$stringArgs}");
        if ($stdin) {
            $fullCommand = trim('echo "'.$stdin.'" | '.$fullCommand);
        }

        if (false === $dryRun) {
            return $this->runner->run($fullCommand, $this->getDockerEnvVariables());
        }

        return $fullCommand;
    }
}
