<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Contracts;

/**
 * Defines the contract for task providers.
 *
 * Task providers are responsible for supplying tasks to the schedule manager.
 * They are only called when the schedule is actually triggered (lazy loading),
 * not on every application bootstrap.
 */
interface TaskProviderInterface
{
    /**
     * Get all tasks provided by this provider.
     *
     * This method is called lazily when tasks are needed (e.g., when running or listing tasks),
     * not during application bootstrap. This allows expensive task configuration to be deferred.
     *
     * @return TaskInterface[] Array of tasks
     */
    public function getTasks(): array;
}
