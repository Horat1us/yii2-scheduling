<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Providers;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Contracts\TaskProviderInterface;

/**
 * Simple provider that returns a static array of tasks.
 *
 * Useful for defining tasks programmatically in bootstrap.
 *
 * Example:
 * ```php
 * $provider = new StaticTaskProvider([
 *     new Task(...),
 *     new Task(...),
 * ]);
 * ```
 */
readonly class StaticTaskProvider implements TaskProviderInterface
{
    /**
     * @param TaskInterface[] $tasks
     */
    public function __construct(
        private array $tasks = [],
    ) {
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }
}
