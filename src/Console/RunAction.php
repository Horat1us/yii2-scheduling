<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Console;

use Carbon\Carbon;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\TaskRunner;
use yii\console\Action;
use yii\helpers\Console;

/**
 * Action that runs all due scheduled tasks.
 *
 * Usage: php yii schedule/run
 *        php yii schedule/run --currentTime="2025-01-01 12:00:00"
 */
class RunAction extends Action
{
    /**
     * Current time for scheduling (defaults to "now").
     * Useful for testing specific times.
     */
    public string $currentTime = 'now';

    private ScheduleManager $scheduleManager;
    private TaskRunner $taskRunner;

    public function __construct(
        $id,
        $controller,
        ?ScheduleManager $scheduleManager = null,
        ?TaskRunner $taskRunner = null,
        $config = []
    ) {
        parent::__construct($id, $controller, $config);
        $this->scheduleManager = $scheduleManager ?? \Yii::$container->get(ScheduleManager::class);
        $this->taskRunner = $taskRunner ?? \Yii::$container->get(TaskRunner::class);
    }

    public function run(): int
    {
        $dateTime = Carbon::parse($this->currentTime);
        $dueTasks = $this->scheduleManager->getDueTasks($dateTime);

        if (empty($dueTasks)) {
            $this->controller->stdout("No tasks are due at {$dateTime->toDateTimeString()}\n", Console::FG_YELLOW);
            return self::EXIT_CODE_NORMAL;
        }

        $this->controller->stdout(
            sprintf("Running %d task(s) at %s\n\n", count($dueTasks), $dateTime->toDateTimeString()),
            Console::FG_CYAN
        );

        // Attach output handlers to events
        $this->attachEventHandlers();

        // Run all due tasks
        $this->taskRunner->runTasks($dueTasks, $dateTime);

        $this->controller->stdout("\nAll tasks completed.\n", Console::FG_GREEN);

        return self::EXIT_CODE_NORMAL;
    }

    private function attachEventHandlers(): void
    {
        // Output task start
        $this->taskRunner->on(TaskRunner::EVENT_BEFORE_TASK, function (BeforeTaskEvent $event) {
            $description = $event->task->getDescription() ?? 'Unnamed task';
            $commandCount = count($event->task->getCommands());

            $this->controller->stdout("→ Starting: ", Console::FG_CYAN);
            $this->controller->stdout("{$description} ({$commandCount} command(s))\n");
        });

        // Output task completion
        $this->taskRunner->on(TaskRunner::EVENT_AFTER_TASK, function (AfterTaskEvent $event) {
            $description = $event->task->getDescription() ?? 'Unnamed task';
            $executionTime = round($event->executionTime, 2);
            $success = $event->isSuccessful();

            if ($success) {
                $this->controller->stdout("✓ Success: ", Console::FG_GREEN);
                $this->controller->stdout("{$description} ({$executionTime}s)\n");
            } else {
                $this->controller->stdout("✗ Failed: ", Console::FG_RED);
                $this->controller->stdout("{$description} ({$executionTime}s)\n");

                // Show error output
                $errorOutput = $event->getErrorOutput();
                if ($errorOutput !== '') {
                    $this->controller->stdout("  Error: {$errorOutput}\n", Console::FG_RED);
                }
            }
        });
    }

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['currentTime']);
    }

    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), ['t' => 'currentTime']);
    }
}
