<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use yii\base\Component;

/**
 * TaskRunner executes scheduled tasks by running all their commands in parallel.
 *
 * It manages process execution, handles timeouts, and triggers events.
 * Tasks don't wait for each other: a slow or timed out task doesn't delay the rest.
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
     * Run multiple tasks in parallel.
     *
     * All tasks are started at once, then the after event is triggered for each task as soon as
     * all its commands have finished.
     *
     * @param TaskInterface[] $tasks Tasks to execute
     * @param \DateTimeInterface $scheduledTime The scheduled execution time
     * @return void
     */
    public function runTasks(array $tasks, \DateTimeInterface $scheduledTime): void
    {
        $runs = [];
        try {
            foreach ($tasks as $task) {
                $runs[] = $this->startTask($task, $scheduledTime);
            }
        } finally {
            // Tasks that already started are waited for even if a later one fails to start
            $this->waitForTasks($runs, $scheduledTime);
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
        $this->runTasks([$task], $scheduledTime);
    }

    /**
     * Trigger the before event and start all task commands.
     *
     * @param TaskInterface $task
     * @param \DateTimeInterface $scheduledTime
     * @return array{task: TaskInterface, processes: Process[], startTime: float, timedOut: bool}
     */
    private function startTask(TaskInterface $task, \DateTimeInterface $scheduledTime): array
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

        return [
            'task' => $task,
            'processes' => $processes,
            'startTime' => $startTime,
            'timedOut' => false,
        ];
    }

    /**
     * Wait for all started tasks, checking timeouts and triggering the after event as each task completes.
     *
     * @param array<array{task: TaskInterface, processes: Process[], startTime: float, timedOut: bool}> $runs
     * @param \DateTimeInterface $scheduledTime
     * @return void
     */
    private function waitForTasks(array $runs, \DateTimeInterface $scheduledTime): void
    {
        while (!empty($runs)) {
            foreach ($runs as $index => $run) {
                $timedOut = $this->checkTimeouts($run['processes']) || $run['timedOut'];
                if ($this->isRunning($run['processes'])) {
                    $runs[$index]['timedOut'] = $timedOut;
                    continue;
                }
                unset($runs[$index]);

                $executionTime = microtime(true) - $run['startTime'];

                // Trigger after event
                $this->trigger(
                    self::EVENT_AFTER_TASK,
                    new AfterTaskEvent($run['task'], $scheduledTime, $run['processes'], $executionTime, $timedOut)
                );
            }

            if (!empty($runs)) {
                // Small sleep to avoid CPU spinning
                usleep(100000); // 0.1 seconds
            }
        }
    }

    /**
     * Stop processes that exceeded their timeout.
     *
     * @param Process[] $processes
     * @return bool Whether any of the processes timed out
     */
    private function checkTimeouts(array $processes): bool
    {
        $timedOut = false;
        foreach ($processes as $process) {
            try {
                $process->checkTimeout();
            } catch (ProcessTimedOutException) {
                // Process timed out, it's already stopped by checkTimeout()
                $timedOut = true;
            }
        }

        return $timedOut;
    }

    /**
     * Check whether any of the processes is still running.
     *
     * Every process is polled, so their output pipes are read even while other processes run.
     *
     * @param Process[] $processes
     * @return bool
     */
    private function isRunning(array $processes): bool
    {
        $isRunning = false;
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $isRunning = true;
            }
        }

        return $isRunning;
    }
}
