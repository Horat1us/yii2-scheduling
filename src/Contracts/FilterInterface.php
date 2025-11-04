<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Contracts;

/**
 * Defines the contract for task filters.
 *
 * Filters determine whether a task should run at a given time.
 * Multiple filters on a task are evaluated with AND logic - all must pass.
 */
interface FilterInterface
{
    /**
     * Check if this filter passes at the given time.
     *
     * @param \DateTimeInterface $dateTime The time to evaluate against
     * @return bool True if the filter passes, false otherwise
     */
    public function passes(\DateTimeInterface $dateTime): bool;

    /**
     * Get a human-readable description of this filter.
     *
     * Used for debugging and display purposes (e.g., in task listings).
     *
     * @return string Description of when this filter passes
     */
    public function getDescription(): string;
}
