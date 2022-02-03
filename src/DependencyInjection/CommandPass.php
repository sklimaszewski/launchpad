<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Launchpad\Configuration\Project as ProjectConfiguration;

class CommandPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public function process(ContainerBuilder $container): void
    {
        $commands = $container->findTaggedServiceIds('sflaunchpad.command');
        foreach ($commands as $id => $tags) {
            $commandDefinition = $container->getDefinition($id);
            $commandDefinition->addMethodCall('setProjectConfiguration', [new Reference(ProjectConfiguration::class)]);
            $commandDefinition->addMethodCall('setAppDir', [$container->getParameter('app_dir')]);
            $commandDefinition->addMethodCall('setProjectPath', [$container->getParameter('project_path')]);
        }
    }
}
