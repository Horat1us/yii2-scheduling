<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit;

use Horat1us\Yii\Schedule\Commands\YiiConsoleCommand;
use Horat1us\Yii\Schedule\Contracts\TaskProviderInterface;
use Horat1us\Yii\Schedule\Filters\BooleanFilter;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\Tasks\Task;
use PHPUnit\Framework\TestCase;

class ScheduleManagerTest extends TestCase
{
    private ScheduleManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ScheduleManager();
    }

    public function testAddTask(): void
    {
        $task = new Task(commands: new YiiConsoleCommand('test/action'));
        $this->manager->addTask($task);

        $tasks = $this->manager->getTasks();
        $this->assertCount(1, $tasks);
        $this->assertSame($task, $tasks[0]);
    }

    public function testAddMultipleTasks(): void
    {
        $task1 = new Task(commands: new YiiConsoleCommand('test/action1'));
        $task2 = new Task(commands: new YiiConsoleCommand('test/action2'));

        $this->manager->addTask($task1);
        $this->manager->addTask($task2);

        $tasks = $this->manager->getTasks();
        $this->assertCount(2, $tasks);
    }

    public function testAddProvider(): void
    {
        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTasks')
            ->willReturn([
                new Task(commands: new YiiConsoleCommand('test/action')),
            ]);

        $this->manager->addProvider($provider);

        $tasks = $this->manager->getTasks();
        $this->assertCount(1, $tasks);
    }

    public function testGetTasksFromMultipleProviders(): void
    {
        $provider1 = $this->createMock(TaskProviderInterface::class);
        $provider1->method('getTasks')->willReturn([
            new Task(commands: new YiiConsoleCommand('task1')),
        ]);

        $provider2 = $this->createMock(TaskProviderInterface::class);
        $provider2->method('getTasks')->willReturn([
            new Task(commands: new YiiConsoleCommand('task2')),
        ]);

        $this->manager->addProvider($provider1);
        $this->manager->addProvider($provider2);

        $tasks = $this->manager->getTasks();
        $this->assertCount(2, $tasks);
    }

    public function testGetTasksCombinesProvidersAndStaticTasks(): void
    {
        $staticTask = new Task(commands: new YiiConsoleCommand('static'));
        $this->manager->addTask($staticTask);

        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->method('getTasks')->willReturn([
            new Task(commands: new YiiConsoleCommand('from-provider')),
        ]);
        $this->manager->addProvider($provider);

        $tasks = $this->manager->getTasks();
        $this->assertCount(2, $tasks);
    }

    public function testGetTasksCachesResults(): void
    {
        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->expects($this->once()) // Should only be called once
            ->method('getTasks')
            ->willReturn([
                new Task(commands: new YiiConsoleCommand('test')),
            ]);

        $this->manager->addProvider($provider);

        // Call getTasks multiple times
        $this->manager->getTasks();
        $this->manager->getTasks();
        $this->manager->getTasks();
    }

    public function testClearCacheInvalidatesCache(): void
    {
        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->expects($this->exactly(2)) // Should be called twice
            ->method('getTasks')
            ->willReturn([
                new Task(commands: new YiiConsoleCommand('test')),
            ]);

        $this->manager->addProvider($provider);

        $this->manager->getTasks(); // First call
        $this->manager->clearCache();
        $this->manager->getTasks(); // Second call after cache clear
    }

    public function testGetDueTasks(): void
    {
        $dueTask = new Task(
            commands: new YiiConsoleCommand('due-task'),
            filters: new BooleanFilter(true)
        );

        $notDueTask = new Task(
            commands: new YiiConsoleCommand('not-due-task'),
            filters: new BooleanFilter(false)
        );

        $this->manager->addTask($dueTask);
        $this->manager->addTask($notDueTask);

        $dueTasks = $this->manager->getDueTasks(new \DateTime());
        $this->assertCount(1, $dueTasks);
    }

    public function testGetDueTasksWithNoTasks(): void
    {
        $dueTasks = $this->manager->getDueTasks(new \DateTime());
        $this->assertCount(0, $dueTasks);
    }

    public function testAddingTaskInvalidatesCache(): void
    {
        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->expects($this->exactly(2))
            ->method('getTasks')
            ->willReturn([]);

        $this->manager->addProvider($provider);
        $this->manager->getTasks(); // Cache

        $this->manager->addTask(new Task(commands: new YiiConsoleCommand('new-task')));
        $this->manager->getTasks(); // Should re-evaluate
    }

    public function testAddingProviderInvalidatesCache(): void
    {
        $task = new Task(commands: new YiiConsoleCommand('task'));
        $this->manager->addTask($task);
        $this->manager->getTasks(); // Cache

        $provider = $this->createMock(TaskProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTasks')
            ->willReturn([]);

        $this->manager->addProvider($provider);
        $this->manager->getTasks(); // Should re-evaluate
    }
}
