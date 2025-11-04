<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Events;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;

/**
 * Event triggered before a task starts executing.
 */
class BeforeTaskEvent extends TaskEvent
{
    /**
     * @param TaskInterface $task The task about to be executed
     * @param \DateTimeInterface $scheduledTime The time when the task was scheduled
     */
    public function __construct(
        TaskInterface $task,
        public readonly \DateTimeInterface $scheduledTime,
    ) {
        parent::__construct($task);
    }
}
