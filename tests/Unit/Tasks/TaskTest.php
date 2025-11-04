<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Tasks;

use Horat1us\Yii\Schedule\Commands\YiiConsoleCommand;
use Horat1us\Yii\Schedule\Commands\ShellCommand;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Exceptions\InvalidTaskException;
use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\Filters\BooleanFilter;
use Horat1us\Yii\Schedule\Filters\ClosureFilter;
use Horat1us\Yii\Schedule\Tasks\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testBasicTask(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('migrate/up'),
            filters: new Cron('* * * * *'),
            timeout: 300,
            description: 'Test task'
        );

        $this->assertInstanceOf(TaskInterface::class, $task);
        $this->assertCount(1, $task->getCommands());
        $this->assertCount(1, $task->getFilters());
        $this->assertSame(300, $task->getTimeout());
        $this->assertSame('Test task', $task->getDescription());
    }

    public function testMultipleCommands(): void
    {
        $task = new Task(
            commands: [
                new YiiConsoleCommand('cache/flush'),
                new ShellCommand('curl https://example.com'),
            ],
            filters: new Cron('* * * * *')
        );

        $this->assertCount(2, $task->getCommands());
    }

    public function testMultipleFilters(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: [
                new Cron('* * * * *'),
                new BooleanFilter(true),
            ]
        );

        $this->assertCount(2, $task->getFilters());
    }

    public function testEmptyCommandsThrowsException(): void
    {
        $this->expectException(InvalidTaskException::class);
        $this->expectExceptionMessage('Task must have at least one command');

        new Task(
            commands: [],
            filters: new Cron('* * * * *')
        );
    }

    public function testInvalidTimeoutThrowsException(): void
    {
        $this->expectException(InvalidTaskException::class);
        $this->expectExceptionMessage('Task timeout must be positive');

        new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: new Cron('* * * * *'),
            timeout: -1
        );
    }

    public function testZeroTimeoutThrowsException(): void
    {
        $this->expectException(InvalidTaskException::class);

        new Task(
            commands: new YiiConsoleCommand('test/action'),
            timeout: 0
        );
    }

    public function testDefaultTimeout(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action')
        );

        $this->assertSame(900, $task->getTimeout()); // 15 minutes
    }

    public function testNullDescription(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action')
        );

        $this->assertNull($task->getDescription());
    }

    public function testBooleanFilterNormalization(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: true // Boolean should be normalized to BooleanFilter
        );

        $filters = $task->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(BooleanFilter::class, $filters[0]);
    }

    public function testClosureFilterNormalization(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: fn(\DateTimeInterface $dt) => true
        );

        $filters = $task->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(ClosureFilter::class, $filters[0]);
    }

    public function testMixedFilterArray(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: [
                new Cron('* * * * *'),
                true,
                fn(\DateTimeInterface $dt) => true,
            ]
        );

        $filters = $task->getFilters();
        $this->assertCount(3, $filters);
        $this->assertInstanceOf(Cron::class, $filters[0]);
        $this->assertInstanceOf(BooleanFilter::class, $filters[1]);
        $this->assertInstanceOf(ClosureFilter::class, $filters[2]);
    }

    public function testEmptyFiltersArray(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action'),
            filters: []
        );

        $this->assertCount(0, $task->getFilters());
    }

    public function testReadonlyProperties(): void
    {
        $task = new Task(
            commands: new YiiConsoleCommand('test/action')
        );

        // Readonly class - properties cannot be modified
        $reflection = new \ReflectionClass($task);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testInvalidCommandInArrayThrowsException(): void
    {
        $this->expectException(InvalidTaskException::class);

        new Task(
            commands: [
                new YiiConsoleCommand('test/action'),
                'invalid-command', // Not a CommandInterface
            ]
        );
    }
}
