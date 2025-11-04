<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit;

use Horat1us\Yii\Schedule\Commands\YiiConsoleCommand;
use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\Filters\BooleanFilter;
use Horat1us\Yii\Schedule\Tasks\Task;
use Horat1us\Yii\Schedule\TaskEvaluator;
use PHPUnit\Framework\TestCase;

class TaskEvaluatorTest extends TestCase
{
    private TaskEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new TaskEvaluator();
    }

    public function testIsDueWithNoFilters(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: []
        );

        $this->assertTrue($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsDueWithPassingFilter(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: new BooleanFilter(true)
        );

        $this->assertTrue($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsNotDueWithFailingFilter(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: new BooleanFilter(false)
        );

        $this->assertFalse($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsDueWithMultiplePassingFilters(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: [
                new BooleanFilter(true),
                new BooleanFilter(true),
                new BooleanFilter(true),
            ]
        );

        $this->assertTrue($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsNotDueWithOneFailingFilterInMultiple(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: [
                new BooleanFilter(true),
                new BooleanFilter(false), // This one fails
                new BooleanFilter(true),
            ]
        );

        $this->assertFalse($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsDueWithCronFilter(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: new Cron('* * * * *') // Every minute
        );

        $this->assertTrue($this->evaluator->isDue($task, new \DateTime()));
    }

    public function testIsNotDueWithCronFilter(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: new Cron('0 1 * * *') // Daily at 1 AM
        );

        $dateTime = new \DateTime('2025-01-01 12:00:00'); // Noon
        $this->assertFalse($this->evaluator->isDue($task, $dateTime));
    }

    public function testFilterDueTasks(): void
    {
        $tasks = [
            new Task(
                commands: new YiiConsoleCommand('task1'),
                filters: new BooleanFilter(true)
            ),
            new Task(
                commands: new YiiConsoleCommand('task2'),
                filters: new BooleanFilter(false)
            ),
            new Task(
                commands: new YiiConsoleCommand('task3'),
                filters: new BooleanFilter(true)
            ),
        ];

        $dueTasks = $this->evaluator->filterDueTasks($tasks, new \DateTime());

        $this->assertCount(2, $dueTasks);
    }

    public function testFilterDueTasksWithEmptyArray(): void
    {
        $dueTasks = $this->evaluator->filterDueTasks([], new \DateTime());

        $this->assertCount(0, $dueTasks);
    }

    public function testFilterDueTasksWithAllDue(): void
    {
        $tasks = [
            new Task(commands: new YiiConsoleCommand('task1')),
            new Task(commands: new YiiConsoleCommand('task2')),
            new Task(commands: new YiiConsoleCommand('task3')),
        ];

        $dueTasks = $this->evaluator->filterDueTasks($tasks, new \DateTime());

        $this->assertCount(3, $dueTasks);
    }

    public function testFilterDueTasksWithNoneDue(): void
    {
        $tasks = [
            new Task(
                commands: new YiiConsoleCommand('task1'),
                filters: new BooleanFilter(false)
            ),
            new Task(
                commands: new YiiConsoleCommand('task2'),
                filters: new BooleanFilter(false)
            ),
        ];

        $dueTasks = $this->evaluator->filterDueTasks($tasks, new \DateTime());

        $this->assertCount(0, $dueTasks);
    }

    public function testAndLogicWithMixedFilters(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: [
                new Cron('* * * * *'), // Passes
                new BooleanFilter(true), // Passes
                fn(\DateTimeInterface $dt) => true, // Passes
            ]
        );

        $this->assertTrue($this->evaluator->isDue($task, new \DateTime()));
    }
}
