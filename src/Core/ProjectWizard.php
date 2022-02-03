<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Launchpad\Core;

use RuntimeException;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Launchpad\Configuration\Project as ProjectConfiguration;

class ProjectWizard
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    public const INIT_STD = 'standard';
    public const INIT_EXPERT = 'expert';

    /**
     * @var array
     */
    protected static $modes = [
        self::INIT_STD,
        self::INIT_EXPERT,
    ];

    /**
     * @var string
     */
    protected $mode;

    public function __construct(SymfonyStyle $io, ProjectConfiguration $configuration)
    {
        $this->io = $io;
        $this->projectConfiguration = $configuration;
    }

    public function __invoke(DockerCompose $compose): array
    {
        $this->mode = $this->getInitializationMode();

        return $this->getConfigurations($compose);
    }

    public function getConfigurations(DockerCompose $compose): array
    {
        return [
            $this->getNetworkName(),
            $this->getNetworkTCPPort(),
            $this->getSelectedServices(
                $compose->getServices(),
                ['adminer', 'redisadmin']
            ),
            $this->getProvisioningFolderName(),
            $this->getComposeFileName(),
            $this->getKubernetesConfig(),
        ];
    }

    public function getInitializationMode(): string
    {
        $standard = self::INIT_STD;
        $expert = self::INIT_EXPERT;
        $question = <<<END
Symfony Launchpad will install a new architecture for you.
 Three modes are available:
  - <fg=cyan>{$standard}</>: All the services, no composer auth
  - <fg=cyan>{$expert}</>: All the questions will be asked and you can select the services you want only
 Please select your <fg=yellow;options=bold>Init</>ialization mode
END;

        return $this->io->choice($question, self::$modes, self::INIT_STD);
    }

    protected function getProvisioningFolderName(): string
    {
        $default = $this->projectConfiguration->get('provisioning.folder_name');
        if (empty($default)) {
            $default = 'docker';
        }

        if ($this->isStandardMode()) {
            return $default;
        }
        $pattern = '^[a-zA-Z0-9]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message = 'What is your preferred name for the <fg=yellow;options=bold>provisioning folder</>?';
        $errorMessage = "The name of the folder MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    public function getComposeFileName(): string
    {
        $default = $this->projectConfiguration->get('docker.compose_file');
        if (empty($default)) {
            $default = 'docker-compose.yml';
        }
        if ($this->isStandardMode()) {
            return $default;
        }

        $pattern = '^[a-zA-Z0-9\-]*\.yml$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message = 'What is your preferred filename for the <fg=yellow;options=bold>Docker Compose file</>?';
        $errorMessage = "The name of the filename MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    public function getKubernetesConfig(): ?array
    {
        if ($this->isStandardMode()) {
            return null;
        }

        if ($this->io->askQuestion(new ConfirmationQuestion('Do you want to setup Kubernetes config?', false))) {
            return [
                'folder_name' => $this->getKubernetesFolderName(),
                'kubeconfig' => $this->getKubeConfigPath(),
                'namespace' => $this->getKubernetesNamespace(),
                'registry' => $this->getContainerRegistry(),
            ];
        }

        return null;
    }

    protected function getKubernetesFolderName(): string
    {
        $default = $this->projectConfiguration->get('kubernetes.folder_name');
        if (empty($default)) {
            $default = 'kubernetes';
        }

        if ($this->isStandardMode()) {
            return $default;
        }
        $pattern = '^[a-zA-Z0-9]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message = 'What is your preferred name for the <fg=yellow;options=bold>kubernetes folder</>?';
        $errorMessage = "The name of the folder MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    protected function getKubeConfigPath(): string
    {
        $default = $this->projectConfiguration->get('kubernetes.kubeconfig');
        if (empty($default)) {
            $default = '~/.kube/config';
        }

        if ($this->isStandardMode()) {
            return $default;
        }

        $validator = function ($value) {
            return file_exists($value);
        };

        $message = 'What is your preferred name for the <fg=yellow;options=bold>kubernetes folder</>?';
        $errorMessage = "Please specify correct filepath.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    protected function getKubernetesNamespace(): string
    {
        $default = $this->projectConfiguration->get('kubernetes.namespace');
        if (empty($default)) {
            $default = 'symfony';
        }

        if ($this->isStandardMode()) {
            return $default;
        }

        $pattern = '^[a-zA-Z0-9\-]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message = 'What is your <fg=yellow;options=bold>project namespace</> in Kubernetes cluster?';
        $errorMessage = "The namespac MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    protected function getContainerRegistry(): array
    {
        if ($this->isStandardMode()) {
            return [
                'name' => null,
                'username' => null,
                'password' => null,
            ];
        }

        $default = $this->projectConfiguration->get('kubernetes.registry.name');

        $validator = function ($value) {
            return substr($value, 0, 4 ) !== 'http' && $value;
        };

        $message = 'What is your <fg=yellow;options=bold>Container Registry</> used for storing images?';
        $errorMessage = "The Container Registry cannot start with HTTP(S).";

        $name = $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
        $username = $this->io->askQuestion($this->getQuestion('What is your registry username?'));
        $password = $this->io->askQuestion($this->getQuestion('What is your registry password?'));

        return [
            'name' => $name,
            'username' => $username,
            'password' => $password,
        ];
    }

    protected function getSelectedServices(array $services, array $questionnable): array
    {
        $selectedServices = [];
        foreach ($services as $name => $service) {
            if (\in_array($name, $questionnable)) {
                if (
                    $this->isStandardMode() ||
                    $this->io->confirm("Do you want the service <fg=yellow;options=bold>{$name}</>")
                ) {
                    $selectedServices[] = $name;
                }
            } else {
                $selectedServices[] = $name;
            }
        }

        return $selectedServices;
    }

    protected function getNetworkName(): string
    {
        $name = getenv('USER').basename(getcwd());
        $default = str_replace(['-', '_', '.'], '', strtolower($name));
        $pattern = '^[a-zA-Z0-9]*$';
        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        if ($validator($default) && $this->isStandardMode()) {
            return $default;
        }

        $message = 'Please select a name for the containers <fg=yellow;options=bold>Docker Network</>';
        $errorMessage = "The name of the network MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    protected function getNetworkTCPPort(): int
    {
        $errno = $errstr = null;
        $default = 0;
        $validator = function ($value) {
            if (($value >= 0) && ($value <= 64)) {
                $socket = @fsockopen('127.0.0.1', (int) "{$value}080", $errno, $errstr, 5);
                if ($socket) {
                    fclose($socket);

                    return false;
                }

                return true;
            }

            return false;
        };

        if ($validator($default) && $this->isStandardMode()) {
            return $default;
        }

        $message = 'What is the <fg=yellow;options=bold>TCP Port Prefix</> you want?';
        $errorMessage = 'The TCP Port Prefix is not correct (already used or not between 0 and 64).';

        return (int) $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @param callable|null $validator
     */
    protected function getQuestion(
        string $message,
        $default = null,
        $validator = null,
        string $exceptionMessage = 'Entry not valid'
    ): Question {
        $question = new Question($message, $default);
        if (\is_callable($validator)) {
            $question->setValidator(
                function ($value) use ($validator, $exceptionMessage) {
                    if (!$validator($value)) {
                        throw new RuntimeException($exceptionMessage);
                    }

                    return $value;
                }
            );
        }

        return $question;
    }

    protected function isStandardMode(): bool
    {
        return self::INIT_STD === $this->mode;
    }

    protected function requireComposerAuth(): bool
    {
        return self::INIT_STD !== $this->mode;
    }
}
