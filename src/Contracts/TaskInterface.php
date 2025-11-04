<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Contracts;

/**
 * Defines the contract for a scheduled task.
 *
 * A task represents a unit of work that should be executed based on filters.
 * Tasks can have multiple commands and multiple filters.
 */
interface TaskInterface
{
    /**
     * Get all commands that should be executed when this task runs.
     *
     * Multiple commands will be executed in parallel.
     *
     * @return CommandInterface[]
     */
    public function getCommands(): array;

    /**
     * Get all filters that determine when this task should run.
     *
     * All filters must pass (AND logic) for the task to be considered due.
     *
     * @return FilterInterface[]
     */
    public function getFilters(): array;

    /**
     * Get the timeout in seconds for this task.
     *
     * If any command exceeds this timeout, it will be terminated.
     *
     * @return int Timeout in seconds
     */
    public function getTimeout(): int;

    /**
     * Get an optional description of this task.
     *
     * @return string|null Human-readable description or null
     */
    public function getDescription(): ?string;
}