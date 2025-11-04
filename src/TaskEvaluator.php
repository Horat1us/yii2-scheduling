<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;

/**
 * Service that evaluates whether tasks should run based on their filters.
 *
 * Separates business logic from data objects, allowing custom task implementations
 * without inheritance.
 */
class TaskEvaluator
{
    /**
     * Check if a task is due to run at the given time.
     *
     * Evaluates all filters with AND logic - all must pass for the task to be due.
     *
     * @param TaskInterface $task The task to evaluate
     * @param \DateTimeInterface $dateTime The time to check against
     * @return bool True if all filters pass (or no filters exist), false otherwise
     */
    public function isDue(TaskInterface $task, \DateTimeInterface $dateTime): bool
    {
        $filters = $task->getFilters();

        if (empty($filters)) {
            return true;
        }

        // All filters must pass (AND logic)
        foreach ($filters as $filter) {
            if (!$filter->passes($dateTime)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter an array of tasks to only those that are due.
     *
     * @param TaskInterface[] $tasks Tasks to filter
     * @param \DateTimeInterface $dateTime The time to check against
     * @return TaskInterface[] Only tasks that are due
     */
    public function filterDueTasks(array $tasks, \DateTimeInterface $dateTime): array
    {
        return array_filter(
            $tasks,
            fn(TaskInterface $task) => $this->isDue($task, $dateTime)
        );
    }
}
