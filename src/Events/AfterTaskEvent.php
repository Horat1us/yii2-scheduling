<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Events;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Symfony\Component\Process\Process;

/**
 * Event triggered after a task finishes executing.
 */
class AfterTaskEvent extends TaskEvent
{
    /**
     * @param TaskInterface $task The task that was executed
     * @param \DateTimeInterface $scheduledTime The time when the task was scheduled
     * @param Process[] $processes The processes that were executed
     * @param float $executionTime Execution time in seconds
     */
    public function __construct(
        TaskInterface $task,
        public readonly \DateTimeInterface $scheduledTime,
        public readonly array $processes,
        public readonly float $executionTime,
    ) {
        parent::__construct($task);
    }

    /**
     * Check if all processes succeeded.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        foreach ($this->processes as $process) {
            if (!$process->isSuccessful()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all process outputs combined.
     *
     * @return string
     */
    public function getOutput(): string
    {
        $outputs = [];
        foreach ($this->processes as $process) {
            $output = trim($process->getOutput());
            if ($output !== '') {
                $outputs[] = $output;
            }
        }
        return implode("\n", $outputs);
    }

    /**
     * Get all process error outputs combined.
     *
     * @return string
     */
    public function getErrorOutput(): string
    {
        $outputs = [];
        foreach ($this->processes as $process) {
            $output = trim($process->getErrorOutput());
            if ($output !== '') {
                $outputs[] = $output;
            }
        }
        return implode("\n", $outputs);
    }
}
