<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use Novactive\Collection\Collection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Launchpad\Core\Client\DockerCompose as DockerComposeClient;

class ProjectStatusDumper
{
    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * @var DockerComposeClient
     */
    protected $dockerClient;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function __construct(ProjectConfiguration $projectConfiguration)
    {
        $this->projectConfiguration = $projectConfiguration;
    }

    public function setDockerClient(DockerComposeClient $dockerClient): void
    {
        $this->dockerClient = $dockerClient;
    }

    public function setIo(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @param Collection|string|array $options
     */
    public function dump($options): void
    {
        if (\is_string($options)) {
            $options = str_split($options);
        }
        if (\is_array($options)) {
            $options = NovaCollection($options);
        }
        if ($options->contains('n')) {
            $this->dumpNetwork();
        }
        if ($options->contains('c')) {
            $this->dumpComposeCommand();
        }

        if ($options->contains('i')) {
            $this->dumpFirstTime();
        }
        if ($options->contains('s')) {
            $this->dumpServiceAccess();
        }
    }

    protected function dumpNetwork(): void
    {
        $this->io->title('Docker Network');
        $this->dockerClient->ps();
    }

    protected function dumpComposeCommand(): void
    {
        $composeCommand = $this->dockerClient->getComposeCommand();
        $this->io->title("\nDocker Compose command");
        $this->io->writeln($composeCommand);
    }

    /**
     * Dump Human service acccess.
     */
    protected function dumpServiceAccess(): void
    {
        $portPrefix = $this->projectConfiguration->get('docker.network_prefix_port');
        $dump = function ($title, $port, $suffix = '', $proto = 'http') use ($portPrefix) {
            $this->io->writeln(
                "<fg=white;options=bold>{$title}: </>".
                str_pad('', 1, "\t").
                "{$proto}://localhost:<fg=white;options=bold>".intval($portPrefix.$port)."</>{$suffix}"
            );
        };
        $this->io->title('Service Access');

        $services = $this->projectConfiguration->getDockerCompose()->getServices();
        if (isset($services['nginx'])) {
            $this->io->section('Project Web Access');
            $dump('Nginx - Front-end (Development Mode)', '080');
            $dump('Nginx - Front-end (Production Mode)', '081');
        }

        if (isset($services['db'])) {
            $this->io->section('Database Access');
            $dump('MariaDB', '306', '', 'tcp');
        }

        if (isset($services['mailcatcher']) || isset($services['adminer']) || isset($services['redisadmin'])) {
            $this->io->section('Tools');
            if (isset($services['mailcatcher'])) {
                $dump('Mailcatcher', '180');
            }
            if (isset($services['adminer'])) {
                $dump('Adminer', '084');
            }
            if (isset($services['redisadmin'])) {
                $dump('Redis Admin', '083');
            }
        }
    }

    /**
     * Dump first time stuff.
     */
    protected function dumpFirstTime(): void
    {
        $this->io->title("\033[2J\033[0;0HWelcome in Symfony Launchpad!");
        $this->io->writeln(
            "Your project environment is now up and running. You have Symfony installed and running.\n".
            'All the information are define in the section <comment>Service Access</comment> below.'
        );

        $this->io->section('Code and CVS');
        $this->io->writeln(
            "You will find the folder <comment>symfony</comment> which contains the project.\n".
            "The <comment>docker</comment> folder contains your local stack specifics.\n".
            "<comment>.gitignore</comment> have been provided then you can commit everything right now.\n"
        );

        $this->io->section('Dump and storage');
        $this->io->writeln(
            "Once intialized you can dump the database and the storage to include it in your repository.\n".
            '<comment>~sf dumpdata</comment> will create a <comment>data</comment> folder containing'.
            ' an archive for each (db.sql.gz and storage.tar.gz).'
        );

        $this->io->section('Sharing');
        $this->io->writeln(
            "Once commited and pushed, your collaborators can just <comment>git pull</comment> to get the project\n".
            "And then run <comment>~/sf create</comment> to duplicate the environment.\n"
        );
    }
}
