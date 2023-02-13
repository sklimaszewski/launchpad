<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Listener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Launchpad\Core\Command;
use Symfony\Launchpad\Core\DockerCommand;
use Symfony\Launchpad\Core\DockerComposeCommand;

final class CommandStart
{
    public function onCommandAction(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof Command) {
            return;
        }

        // Ensure that docker is running
        $nonDockerCommandCheckList = [
            'docker:initialize:skeleton', 'docker:initialize',
        ];
        if (($command instanceof DockerCommand) || ($command instanceof DockerComposeCommand) || (\in_array($command->getName(), $nonDockerCommandCheckList))) {
            $output = $return = null;
            exec('docker system info > /dev/null 2>&1', $output, $return);
            if (0 !== $return) {
                $io = new SymfonyStyle($event->getInput(), $event->getOutput());
                $io->error('You need to start Docker before to run that command.');
                $event->disableCommand();
                $event->stopPropagation();

                return;
            }
        }

        $fs = new Filesystem();
        $command->getRequiredRecipes()->each(
            function ($recipe) use ($fs, $command) {
                $fs->copy(
                    "{$command->getPayloadDir()}/recipes/{$recipe}.bash",
                    "{$command->getProjectPath()}/{$recipe}.bash",
                    true
                );
                $fs->chmod("{$command->getProjectPath()}/{$recipe}.bash", 0755);
            }
        );

        // MacOS may have slower FS, wait 3 seconds
        if ($command->getRequiredRecipes()->count() > 0 && SF_ON_OSX) {
            sleep(3);
        }
    }
}
