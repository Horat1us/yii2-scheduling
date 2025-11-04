<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Events;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use yii\base\Event;

/**
 * Base event class for task-related events.
 */
class TaskEvent extends Event
{
    public function __construct(
        public readonly TaskInterface $task,
    ) {
        parent::__construct();
    }
}
