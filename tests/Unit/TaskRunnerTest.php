<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit;

use Horat1us\Yii\Schedule\Commands\ShellCommand;
use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\Tasks\Task;
use Horat1us\Yii\Schedule\TaskRunner;
use PHPUnit\Framework\TestCase;

class TaskRunnerTest extends TestCase
{
    private string $directory;

    /** @var string[] Task events in the order they were triggered */
    private array $events = [];

    /** @var array<string, bool> Task success by description, in the order tasks finished */
    private array $results = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/yii2-scheduling-' . uniqid();
        mkdir($this->directory);
        $this->events = [];
        $this->results = [];
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*'));
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testRunTaskTriggersEventsWithProcessResults(): void
    {
        $task = new Task(
            commands: [
                new ShellCommand('echo first'),
                new ShellCommand('echo second'),
            ],
            description: 'echo',
        );
        $scheduledTime = new \DateTime('2025-01-01 12:00:00');

        $after = null;
        $runner = $this->createRunner();
        $runner->on(TaskRunner::EVENT_AFTER_TASK, function (AfterTaskEvent $event) use (&$after) {
            $after = $event;
        });
        $runner->runTask($task, $scheduledTime);

        $this->assertSame(['before:echo', 'after:echo'], $this->events);
        $this->assertInstanceOf(AfterTaskEvent::class, $after);
        $this->assertSame($task, $after->task);
        $this->assertSame($scheduledTime, $after->scheduledTime);
        $this->assertCount(2, $after->processes);
        $this->assertTrue($after->isSuccessful());
        $this->assertSame("first\nsecond", $after->getOutput());
    }

    public function testRunTasksRunsTasksInParallel(): void
    {
        // The first task can only finish after the second one has started
        $marker = escapeshellarg($this->directory . '/started');
        $waiting = new Task(
            commands: new ShellCommand("while [ ! -f {$marker} ]; do sleep 0.05; done"),
            timeout: 5,
            description: 'waiting',
        );
        $marking = new Task(
            commands: new ShellCommand("touch {$marker}"),
            description: 'marking',
        );

        $this->createRunner()->runTasks([$waiting, $marking], new \DateTime());

        // Both tasks may finish within the same poll, so only the start order is fixed
        $this->assertSame(['before:waiting', 'before:marking'], array_slice($this->events, 0, 2));
        $this->assertEquals(['marking' => true, 'waiting' => true], $this->results);
    }

    public function testTimedOutTaskDoesNotDelayOtherTasks(): void
    {
        $slow = new Task(
            commands: new ShellCommand('sleep 10'),
            timeout: 1,
            description: 'slow',
        );
        $fast = new Task(
            commands: new ShellCommand('echo fast'),
            description: 'fast',
        );

        $startTime = microtime(true);
        $this->createRunner()->runTasks([$slow, $fast], new \DateTime());

        $this->assertLessThan(5, microtime(true) - $startTime);
        $this->assertSame(
            ['before:slow', 'before:fast', 'after:fast', 'after:slow'],
            $this->events
        );
        $this->assertSame(['fast' => true, 'slow' => false], $this->results);
    }

    public function testStartedTasksAreAwaitedWhenLaterTaskFailsToStart(): void
    {
        $failingCommand = $this->createStub(CommandInterface::class);
        $failingCommand->method('toProcess')->willThrowException(new \RuntimeException('Unable to start'));

        $started = new Task(
            commands: new ShellCommand('sleep 0.2'),
            description: 'started',
        );
        $failing = new Task(
            commands: $failingCommand,
            description: 'failing',
        );

        try {
            $this->createRunner()->runTasks([$started, $failing], new \DateTime());
            $this->fail('Start failure must be rethrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to start', $exception->getMessage());
        }

        $this->assertSame(['before:started', 'before:failing', 'after:started'], $this->events);
        $this->assertSame(['started' => true], $this->results);
    }

    private function createRunner(): TaskRunner
    {
        $runner = new TaskRunner();
        $runner->on(TaskRunner::EVENT_BEFORE_TASK, function (BeforeTaskEvent $event) {
            $this->events[] = 'before:' . $event->task->getDescription();
        });
        $runner->on(TaskRunner::EVENT_AFTER_TASK, function (AfterTaskEvent $event) {
            $this->events[] = 'after:' . $event->task->getDescription();
            $this->results[$event->task->getDescription()] = $event->isSuccessful();
        });

        return $runner;
    }
}
