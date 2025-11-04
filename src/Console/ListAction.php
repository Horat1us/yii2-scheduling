<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Console;

use Carbon\Carbon;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\TaskEvaluator;
use yii\base;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Action that lists all scheduled tasks with their status.
 *
 * Usage: php yii schedule/list
 */
class ListAction extends base\Action
{
    private ScheduleManager $scheduleManager;
    private TaskEvaluator $evaluator;

    public function __construct(
        $id,
        $controller,
        ?ScheduleManager $scheduleManager = null,
        ?TaskEvaluator $evaluator = null,
        $config = []
    ) {
        parent::__construct($id, $controller, $config);
        $this->scheduleManager = $scheduleManager ?? \Yii::$container->get(ScheduleManager::class);
        $this->evaluator = $evaluator ?? new TaskEvaluator();
    }

    public function run(): int
    {
        $tasks = $this->scheduleManager->getTasks();

        if (empty($tasks)) {
            $this->controller->stdout("No scheduled tasks configured.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $now = Carbon::parse($this->controller->currentTime);
        $this->controller->stdout(
            sprintf("Scheduled Tasks (current time: %s)\n\n", $now->toDateTimeString()),
            Console::BOLD
        );

        foreach ($tasks as $index => $task) {
            $this->displayTask($task, $index + 1, $now);
        }

        return ExitCode::OK;
    }

    private function displayTask(TaskInterface $task, int $number, Carbon $now): void
    {
        $isDue = $this->evaluator->isDue($task, $now);
        $description = $task->getDescription() ?? 'Unnamed task';
        $timeout = $task->getTimeout();
        $commandCount = count($task->getCommands());
        $filterCount = count($task->getFilters());

        // Status indicator
        if ($isDue) {
            $status = $this->controller->ansiFormat('DUE', Console::FG_GREEN);
        } else {
            $status = $this->controller->ansiFormat('PENDING', Console::FG_YELLOW);
        }

        // Task header
        $this->controller->stdout(sprintf(
            "[%d] %s [%s]\n",
            $number,
            $description,
            $status
        ), Console::BOLD);

        // Task details
        $this->controller->stdout("    Commands: {$commandCount}\n");
        $this->controller->stdout("    Timeout: {$timeout}s\n");
        $this->controller->stdout("    Filters: {$filterCount}\n");

        // Display filters
        if ($filterCount > 0) {
            foreach ($task->getFilters() as $filter) {
                $this->controller->stdout("      - {$filter->getDescription()}\n", Console::FG_GREY);

                // Show next/prev run for cron filters
                if ($filter instanceof Cron) {
                    $next = $filter->getNextRunDate($now);
                    $prev = $filter->getPreviousRunDate($now);
                    $this->controller->stdout(
                        "        Next: {$next->format('Y-m-d H:i:s')} | Prev: {$prev->format('Y-m-d H:i:s')}\n",
                        Console::FG_GREY
                    );
                }
            }
        }

        // Display commands
        foreach ($task->getCommands() as $command) {
            $this->controller->stdout("      → {$command->getDescription()}\n", Console::FG_CYAN);
        }

        $this->controller->stdout("\n");
    }
}
