<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core\OSX\Optimizer;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Launchpad\Core\Command;

interface OptimizerInterface
{
    public function isEnabled(): bool;

    public function hasPermission(SymfonyStyle $io): bool;

    public function optimize(SymfonyStyle $io, Command $command);

    public function supports(int $version): bool;
}
