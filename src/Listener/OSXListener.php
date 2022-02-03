<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Listener;

use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Launchpad\Core\Command;
use Symfony\Launchpad\Core\OSX\Optimizer\OptimizerInterface;

class OSXListener
{
    /**
     * @var array
     */
    protected $optimizers;

    public function __construct(iterable $optimizers)
    {
        $this->optimizers = $optimizers;
    }

    public function onCommandAction(ConsoleCommandEvent $event): void
    {
        if (!SF_ON_OSX) {
            return;
        }
        $command = $event->getCommand();
        if (!$command instanceof Command) {
            return;
        }

        // don't bother for those command
        if (\in_array($command->getName(), ['self-update', 'rollback', 'list', 'help', 'test'])) {
            return;
        }

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());

        try {
            $version = $this->getDockerVersion();
            foreach ($this->optimizers as $optimizer) {
                /** @var OptimizerInterface $optimizer */
                if (
                    $optimizer->supports($version) &&
                    !$optimizer->isEnabled() &&
                    $optimizer->hasPermission($io)
                ) {
                    $optimizer->optimize($io, $command);
                    // only one allowed
                    break;
                }
            }
            // one of them has been enable
            foreach ($this->optimizers as $optimizer) {
                /** @var OptimizerInterface $optimizer */
                if ($optimizer->supports($version) && $optimizer->isEnabled()) {
                    $command->setOptimizer($optimizer);
                }
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            $event->disableCommand();
            $event->stopPropagation();

            return;
        }
    }

    protected function getDockerVersion(): int
    {
        $output = $return = null;
        exec('docker -v 2>/dev/null', $output, $return);
        if (0 !== $return) {
            throw new RuntimeException('You need to install Docker for Mac before to run that command.');
        }
        list($version, $build) = explode(',', $output[0]);
        unset($build);
        $result = preg_replace('/([^ ]*) ([^ ]*) ([0-9\\.]*)-?([a-zA-z]*)/ui', '$3', $version);
        list($major, $minor, $patch) = explode('.', $result);
        unset($patch);
        $normalizedVersion = (int) $major * 100 + $minor;

        return (int) $normalizedVersion;
    }
}
