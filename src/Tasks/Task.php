<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tasks;

use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Horat1us\Yii\Schedule\Contracts\FilterInterface;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Exceptions\InvalidTaskException;
use Horat1us\Yii\Schedule\Filters\BooleanFilter;
use Horat1us\Yii\Schedule\Filters\ClosureFilter;

/**
 * Concrete task implementation with support for multiple commands and filters.
 *
 * This is a plain data object that holds task configuration.
 * Business logic (like checking if a task is due) is handled by separate services.
 *
 * Example:
 * ```php
 * new Task(
 *     commands: [
 *         new YiiConsoleCommand('cache/flush'),
 *         new ShellCommand('curl https://example.com/ping'),
 *     ],
 *     filters: [
 *         new Cron('0 1 * * *'),
 *         new Environment('prod'),
 *     ],
 *     timeout: 300,
 *     description: 'Daily cache flush and health check'
 * );
 * ```
 */
readonly class Task implements TaskInterface
{
    /** @var CommandInterface[] */
    private array $commands;

    /** @var FilterInterface[] */
    private array $filters;

    /**
     * @param CommandInterface|CommandInterface[] $commands One or more commands to execute
     * @param FilterInterface|bool|\Closure|array $filters Filters determining when to run
     * @param int $timeout Timeout in seconds (default: 15 minutes)
     * @param string|null $description Human-readable description
     *
     * @throws InvalidTaskException
     */
    public function __construct(
        CommandInterface|array $commands,
        FilterInterface|bool|\Closure|array $filters = [],
        private int $timeout = 900,
        private ?string $description = null,
    ) {
        // Normalize commands to array
        $this->commands = is_array($commands) ? $commands : [$commands];

        if (empty($this->commands)) {
            throw InvalidTaskException::emptyCommands();
        }

        foreach ($this->commands as $command) {
            if (!$command instanceof CommandInterface) {
                throw InvalidTaskException::invalidCommand($command);
            }
        }

        // Normalize filters to array
        $this->filters = $this->normalizeFilters($filters);

        // Validate timeout
        if ($this->timeout <= 0) {
            throw InvalidTaskException::invalidTimeout($this->timeout);
        }
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Normalize various filter input formats to FilterInterface array.
     *
     * @param FilterInterface|bool|\Closure|array $filters
     * @return FilterInterface[]
     */
    private function normalizeFilters(FilterInterface|bool|\Closure|array $filters): array
    {
        // Single filter
        if ($filters instanceof FilterInterface) {
            return [$filters];
        }

        // Boolean (e.g., YII_ENV_PROD constant)
        if (is_bool($filters)) {
            return [new BooleanFilter($filters)];
        }

        // Closure
        if ($filters instanceof \Closure) {
            return [new ClosureFilter($filters)];
        }

        // Array of filters
        if (is_array($filters)) {
            $normalized = [];
            foreach ($filters as $filter) {
                if ($filter instanceof FilterInterface) {
                    $normalized[] = $filter;
                } elseif (is_bool($filter)) {
                    $normalized[] = new BooleanFilter($filter);
                } elseif ($filter instanceof \Closure) {
                    $normalized[] = new ClosureFilter($filter);
                } else {
                    throw InvalidTaskException::invalidCommand($filter);
                }
            }
            return $normalized;
        }

        return [];
    }
}
