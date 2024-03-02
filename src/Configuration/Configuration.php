<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sflaunchpad');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('docker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('compose_filename')->defaultValue('docker-compose.yml')->end()
                        ->scalarNode('network_name')->defaultValue('default-sflaunchpad')->end()
                        ->scalarNode('network_prefix_port')->defaultValue(0)->end()
                        ->scalarNode('host_machine_mapping')->defaultNull()->end()
                        ->scalarNode('host_composer_cache_dir')->defaultNull()->end()
                        ->arrayNode('storage_dirs')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('variables')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('context')->defaultNull()->end()
                        ->scalarNode('main_container')->defaultValue('symfony')->end()
                    ->end()
                ->end()
                ->arrayNode('provisioning')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('folder_name')->defaultValue('docker')->end()
                        ->scalarNode('project_folder_name')->defaultValue('symfony')->end()
                        ->scalarNode('data_folder_name')->defaultValue('data')->end()
                        ->arrayNode('storage_dirs')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('kubernetes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('folder_name')->defaultValue('kubernetes')->end()
                        ->scalarNode('kubeconfig')->defaultNull()->end()
                        ->scalarNode('namespace')->defaultValue('symfony')->end()
                        ->arrayNode('registry')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultNull()->end()
                                ->scalarNode('username')->defaultNull()->end()
                                ->scalarNode('password')->defaultNull()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('composer')
                    ->children()
                        ->arrayNode('http_basic')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('host')->defaultNull()->end()
                                    ->scalarNode('login')->defaultNull()->end()
                                    ->scalarNode('password')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('token')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('host')->defaultNull()->end()
                                    ->scalarNode('value')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('last_update_check')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
