<?php

namespace Symfony\Launchpad\Core\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;
use Symfony\Launchpad\Core\ProcessRunner;

class Helm
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
            'kubeconfig-file' => null,
            'helm-chart-path' => null,
            'namespace' => null,
        ];
        $resolver->setDefaults($defaults);
        $resolver->setRequired(array_keys($defaults));
        $resolver->setAllowedTypes('registry-name', ['null', 'string']);
        $resolver->setAllowedTypes('registry-username', ['null', 'string']);
        $resolver->setAllowedTypes('registry-password', ['null', 'string']);
        $resolver->setAllowedTypes('kubeconfig-file', 'string');
        $resolver->setAllowedTypes('helm-chart-path', 'string');
        $resolver->setAllowedTypes('namespace', 'string');
        $this->options = $resolver->resolve($options);
        $this->runner = $runner;
    }

    public function deploy(string $tag, ?string $name = null)
    {
        $flags = [
            'install' => '',
            'values' => rtrim($this->getHelmChart(), '/').'/values.yaml',
            'create-namespace' => '',
            'namespace' => $this->getNamespace(),
            'set-string' => $this->getChartValues($tag),
        ];

        return $this->perform('upgrade', $name ?: $this->getNamespace(), $flags);
    }

    public function dependencyUpdate()
    {
        return $this->perform('dependency update', '');
    }

    protected function getKubeConfigFile(): string
    {
        return $this->options['kubeconfig-file'];
    }

    protected function getHelmChart(): string
    {
        return $this->options['helm-chart-path'];
    }

    protected function getContainerRegistry(): string
    {
        return $this->options['registry-name'];
    }

    protected function getContainerRegistryAuth(): string
    {
        return base64_encode(json_encode([
            'auths' => [
                $this->getContainerRegistryHost() => [
                    'username' => $this->options['registry-username'],
                    'password' => $this->options['registry-password'],
                    'auth' => base64_encode(
                        $this->options['registry-username'].':'.$this->options['registry-password']
                    ),
                ],
            ],
        ]));
    }

    protected function getContainerRegistryHost(): string
    {
        return parse_url('//'.$this->options['registry-name'], PHP_URL_HOST);
    }

    protected function getNamespace(): string
    {
        return $this->options['namespace'];
    }

    protected function getChartValues(string $tag): array
    {
        return [
            'symfony.image.tag' => $tag,
            'symfony.image.registry' => $this->getContainerRegistry(),
            'symfony.image.pullSecretConfig' => $this->getContainerRegistryAuth(),
            'namespace' => $this->getNamespace(),
        ];
    }

    /**
     * @return Process|string
     */
    protected function perform(string $action, string $name, array $flags = [], bool $dryRun = false)
    {
        if (!isset($flags['kubeconfig'])) {
            $flags['kubeconfig'] = $this->getKubeConfigFile();
        }

        $fullCommand = trim("helm {$action} {$name} {$this->getHelmChart()} {$this->convertFlagsToArgument($flags)}");
        if (false === $dryRun) {
            return $this->runner->run($fullCommand, []);
        }

        return $fullCommand;
    }

    private function convertFlagsToArgument(array $flags = []): string
    {
        $args = [];

        foreach ($flags as $key => $value) {
            if (is_array($value)) {
                $variables = [];
                foreach ($value as $subKey => $subValue) {
                    $variables[] = $subKey.'='.$subValue;
                }
                $value = implode(',', $variables);
            }

            $args[] = trim('--'.$key.' '.$value);
        }

        return implode(' ', $args);
    }
}
