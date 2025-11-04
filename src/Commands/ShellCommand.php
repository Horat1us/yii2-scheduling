<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Commands;

use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Symfony\Component\Process\Process;

/**
 * Command that executes a raw shell command.
 *
 * Example: new ShellCommand('php artisan queue:work')
 *          new ShellCommand('curl https://example.com/ping')
 */
readonly class ShellCommand implements CommandInterface
{
    /**
     * @param string $command The shell command to execute
     * @param string|null $description Optional human-readable description
     */
    public function __construct(
        private string $command,
        private ?string $description = null,
    ) {
    }

    public function toProcess(): Process
    {
        return Process::fromShellCommandline($this->command);
    }

    public function getDescription(): string
    {
        return $this->description ?? $this->command;
    }
}
