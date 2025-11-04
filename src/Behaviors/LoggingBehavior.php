<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Behaviors;

use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\TaskRunner;
use yii\base\Behavior;
use Yii;

/**
 * LoggingBehavior logs task execution using Yii2's logging system.
 *
 * Attach this behavior to TaskRunner to enable automatic logging:
 * ```php
 * $runner = \Yii::$container->get(TaskRunner::class);
 * $runner->attachBehavior('logging', LoggingBehavior::class);
 * ```
 *
 * Or configure in the application config:
 * ```php
 * 'container' => [
 *     'definitions' => [
 *         TaskRunner::class => [
 *             'as logging' => LoggingBehavior::class,
 *         ],
 *     ],
 * ],
 * ```
 */
class LoggingBehavior extends Behavior
{
    /**
     * Log category for scheduled tasks.
     */
    public string $logCategory = 'schedule';

    /**
     * Whether to log task start.
     */
    public bool $logStart = true;

    /**
     * Whether to log task output.
     */
    public bool $logOutput = true;

    /**
     * Whether to log task error output.
     */
    public bool $logErrorOutput = true;

    public function events(): array
    {
        return [
            TaskRunner::EVENT_BEFORE_TASK => 'onBeforeTask',
            TaskRunner::EVENT_AFTER_TASK => 'onAfterTask',
        ];
    }

    /**
     * Log task start.
     *
     * @param BeforeTaskEvent $event
     * @return void
     */
    public function onBeforeTask(BeforeTaskEvent $event): void
    {
        if (!$this->logStart) {
            return;
        }

        $description = $event->task->getDescription() ?? 'Unnamed task';
        $commandCount = count($event->task->getCommands());
        $filterCount = count($event->task->getFilters());

        Yii::info(
            "Starting task: {$description} ({$commandCount} commands, {$filterCount} filters)",
            $this->logCategory
        );
    }

    /**
     * Log task completion.
     *
     * @param AfterTaskEvent $event
     * @return void
     */
    public function onAfterTask(AfterTaskEvent $event): void
    {
        $description = $event->task->getDescription() ?? 'Unnamed task';
        $executionTime = round($event->executionTime, 2);
        $processCount = count($event->processes);
        $success = $event->isSuccessful();

        $message = sprintf(
            "Task %s: %s (%d processes, %.2fs)",
            $success ? 'completed' : 'failed',
            $description,
            $processCount,
            $executionTime
        );

        if ($success) {
            Yii::info($message, $this->logCategory);
        } else {
            Yii::error($message, $this->logCategory);
        }

        // Log output if enabled
        if ($this->logOutput) {
            $output = $event->getOutput();
            if ($output !== '') {
                Yii::info("Task output:\n{$output}", $this->logCategory);
            }
        }

        // Log error output if enabled
        if ($this->logErrorOutput) {
            $errorOutput = $event->getErrorOutput();
            if ($errorOutput !== '') {
                Yii::error("Task error output:\n{$errorOutput}", $this->logCategory);
            }
        }
    }
}
