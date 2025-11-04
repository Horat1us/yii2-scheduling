<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Commands;

use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Symfony\Component\Process\Process;

/**
 * Command that executes a Yii2 console route.
 *
 * Example: new YiiConsoleCommand('migrate/up', ['--interactive' => 0])
 */
readonly class YiiConsoleCommand implements CommandInterface
{
    /**
     * @param string $route Yii2 console route (e.g., 'migrate/up', 'cache/flush')
     * @param array<string, mixed> $options Command-line options
     * @param string|null $yiiExecutable Path to yii executable (defaults to './yii')
     */
    public function __construct(
        private string $route,
        private array $options = [],
        private ?string $yiiExecutable = null,
    ) {
    }

    public function toProcess(): Process
    {
        $command = [
            PHP_BINARY,
            $this->yiiExecutable ?? './yii',
            $this->route,
        ];

        foreach ($this->options as $key => $value) {
            if (is_int($key)) {
                $command[] = $this->escapeArgument((string)$value);
            } elseif ($value === true) {
                $command[] = "--{$key}";
            } elseif ($value !== false && $value !== null) {
                $command[] = "--{$key}=" . $this->escapeArgument((string)$value);
            }
        }

        return Process::fromShellCommandline(implode(' ', $command));
    }

    public function getDescription(): string
    {
        $options = [];
        foreach ($this->options as $key => $value) {
            if (is_int($key)) {
                $options[] = (string)$value;
            } elseif ($value === true) {
                $options[] = "--{$key}";
            } elseif ($value !== false && $value !== null) {
                $options[] = "--{$key}={$value}";
            }
        }

        $optionsString = $options ? ' ' . implode(' ', $options) : '';
        return "yii {$this->route}{$optionsString}";
    }

    private function escapeArgument(string $argument): string
    {
        if (str_contains($argument, ' ') || str_contains($argument, '"') || str_contains($argument, "'")) {
            return '"' . addcslashes($argument, '"\\') . '"';
        }
        return $argument;
    }
}
