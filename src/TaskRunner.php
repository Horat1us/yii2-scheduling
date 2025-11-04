<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Symfony\Component\Process\Process;
use yii\base\Component;

/**
 * TaskRunner executes scheduled tasks by running their commands in parallel.
 *
 * It manages process execution, handles timeouts, and triggers events.
 *
 * Usage:
 * ```php
 * $runner = \Yii::$container->get(TaskRunner::class);
 * $runner->runTasks($tasks, new \DateTime());
 * ```
 */
class TaskRunner extends Component
{
    /**
     * Event triggered before a task starts executing.
     */
    public const EVENT_BEFORE_TASK = 'beforeTask';

    /**
     * Event triggered after a task finishes executing.
     */
    public const EVENT_AFTER_TASK = 'afterTask';

    /**
     * Run multiple tasks, executing all their commands in parallel.
     *
     * @param TaskInterface[] $tasks Tasks to execute
     * @param \DateTimeInterface $scheduledTime The scheduled execution time
     * @return void
     */
    public function runTasks(array $tasks, \DateTimeInterface $scheduledTime): void
    {
        foreach ($tasks as $task) {
            $this->runTask($task, $scheduledTime);
        }
    }

    /**
     * Run a single task, executing all its commands in parallel.
     *
     * @param TaskInterface $task Task to execute
     * @param \DateTimeInterface $scheduledTime The scheduled execution time
     * @return void
     */
    public function runTask(TaskInterface $task, \DateTimeInterface $scheduledTime): void
    {
        // Trigger before event
        $this->trigger(self::EVENT_BEFORE_TASK, new BeforeTaskEvent($task, $scheduledTime));

        $startTime = microtime(true);

        // Create and start all processes in parallel
        $processes = [];
        foreach ($task->getCommands() as $command) {
            $process = $command->toProcess();
            $process->setTimeout($task->getTimeout());
            $process->start();
            $processes[] = $process;
        }

        // Monitor all processes until they complete
        $this->waitForProcesses($processes);

        $executionTime = microtime(true) - $startTime;

        // Trigger after event
        $this->trigger(
            self::EVENT_AFTER_TASK,
            new AfterTaskEvent($task, $scheduledTime, $processes, $executionTime)
        );
    }

    /**
     * Wait for all processes to complete, checking timeouts.
     *
     * @param Process[] $processes
     * @return void
     */
    private function waitForProcesses(array $processes): void
    {
        if (empty($processes)) {
            return;
        }

        $iterator = new \InfiniteIterator(new \ArrayIterator($processes));

        foreach ($iterator as $index => $process) {
            // Check timeout
            try {
                $process->checkTimeout();
            } catch (\Throwable) {
                // Process timed out, it's already stopped by checkTimeout()
            }

            // Remove completed processes
            if (!$process->isRunning()) {
                unset($processes[$index]);
            }

            // All processes completed
            if (empty($processes)) {
                break;
            }

            // Small sleep to avoid CPU spinning
            usleep(100000); // 0.1 seconds
        }
    }
}
