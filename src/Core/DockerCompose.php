<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Symfony\Launchpad\Core;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class DockerCompose
{
    /**
     * @var array
     */
    protected $compose;

    public function __construct(string $filePath)
    {
        $this->compose = Yaml::parse(file_get_contents($filePath));
    }

    public function getServices(): array
    {
        return $this->compose['services'];
    }

    public function hasService(string $name): bool
    {
        return isset($this->compose['services'][$name]);
    }

    public function dump(string $destination): void
    {
        $yaml = Yaml::dump($this->compose, 4);
        $fs = new Filesystem();
        $fs->dumpFile($destination, $yaml);
    }

    public function filterServices(array $selectedServices): void
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            if (!\in_array($name, $selectedServices)) {
                continue;
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }

    public function cleanForInitializeSkeleton(): void
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }

    public function cleanForInitialize(): void
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            // we don't need anything else for the init
            if (!\in_array($name, ['symfony', 'db'])) {
                continue;
            }

            if (isset($service['environment'])) {
                $environnementVars = NovaCollection($service['environment']);
                $service['environment'] = $environnementVars->prune(
                    function ($value) {
                        $vars = [
                            'CUSTOM_CACHE_POOL',
                            'CACHE_HOST',
                            'CACHE_REDIS_PORT',
                            'CACHE_POOL',
                            'CACHE_DSN',
                            'SEARCH_ENGINE',
                            'HTTPCACHE_PURGE_SERVER',
                            'SYMFONY_TMP_DIR',
                        ];

                        return !preg_match(
                            '/('.implode('|', $vars).')/',
                            $value
                        );
                    }
                )->values()->toArray();
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }

    public function removeUselessEnvironmentsVariables(int $majorVersion): void
    {
        $services = [];
        foreach ($this->getServices() as $name => $service) {
            if (isset($service['environment'])) {
                $environnementVars = NovaCollection($service['environment']);
                $service['environment'] = $environnementVars->prune(
                    function ($value) use ($majorVersion) {
                        if (!$this->hasService('redis')) {
                            if (
                                preg_match(
                                    '/(CUSTOM_CACHE_POOL|CACHE_HOST|CACHE_POOL|CACHE_DSN|CACHE_REDIS_PORT)/',
                                    $value
                                )
                            ) {
                                return false;
                            }
                        }

                        if ($majorVersion <= 3) {
                            if (preg_match('/(DATABASE_URL|MAILER_DSN)/', $value)) {
                                return false;
                            }
                        } else {
                            if (
                                preg_match(
                                    '/(DATABASE_PLATFORM|DATABASE_DRIVER|DATABASE_USER|DATABASE_NAME|DATABASE_PASSWORD|DATABASE_HOST|MAILER_TRANSPORT|MAILER_HOST|MAILER_PORT)/',
                                    $value
                                )
                            ) {
                                return false;
                            }
                        }

                        return true;
                    }
                )->values()->toArray();
            }
            $services[$name] = $service;
        }
        $this->compose['services'] = $services;
    }
}
