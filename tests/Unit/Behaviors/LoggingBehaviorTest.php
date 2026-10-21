<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Behaviors;

use Horat1us\Yii\Schedule\Behaviors\LoggingBehavior;
use Horat1us\Yii\Schedule\Commands\ShellCommand;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\Tasks\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use yii\log\Logger;

class LoggingBehaviorTest extends TestCase
{
    private Logger $originalLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalLogger = \Yii::getLogger();
        \Yii::setLogger(new Logger());
    }

    protected function tearDown(): void
    {
        \Yii::setLogger($this->originalLogger);

        parent::tearDown();
    }

    public function testLogsTaskStart(): void
    {
        $behavior = new LoggingBehavior();
        $behavior->onBeforeTask(new BeforeTaskEvent($this->createTask(), new \DateTime()));

        $this->assertSame(
            [[Logger::LEVEL_INFO, 'schedule', 'Starting task: Export (1 commands, 0 filters)']],
            $this->getLoggedMessages()
        );
    }

    public function testLogsCompletedTaskWithOutput(): void
    {
        $behavior = new LoggingBehavior();
        $behavior->onAfterTask($this->createEvent(successful: true, output: 'Exported 2 requests'));

        $this->assertSame(
            [
                [Logger::LEVEL_INFO, 'schedule', 'Task completed: Export (1 processes, 1.23s)'],
                [Logger::LEVEL_INFO, 'schedule', "Task output:\nExported 2 requests"],
            ],
            $this->getLoggedMessages()
        );
    }

    public function testLogsFailedTaskWithoutRunDetails(): void
    {
        $behavior = new LoggingBehavior();
        $behavior->onAfterTask($this->createEvent(successful: false, errorOutput: 'Error: Connection refused'));

        $this->assertSame(
            [
                [Logger::LEVEL_ERROR, 'schedule', 'Task failed: Export'],
                [Logger::LEVEL_ERROR, 'schedule', "Task error output:\nError: Connection refused"],
            ],
            $this->getLoggedMessages()
        );
    }

    public function testLogsTimedOutTask(): void
    {
        $behavior = new LoggingBehavior();
        $behavior->onAfterTask($this->createEvent(successful: false, timedOut: true));

        $this->assertSame(
            [[Logger::LEVEL_ERROR, 'schedule', 'Task timed out after 60s: Export']],
            $this->getLoggedMessages()
        );
    }

    private function createTask(): Task
    {
        return new Task(
            commands: new ShellCommand('true'),
            timeout: 60,
            description: 'Export',
        );
    }

    private function createEvent(
        bool $successful,
        bool $timedOut = false,
        string $output = '',
        string $errorOutput = '',
    ): AfterTaskEvent {
        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn($successful);
        $process->method('getOutput')->willReturn($output);
        $process->method('getErrorOutput')->willReturn($errorOutput);

        return new AfterTaskEvent($this->createTask(), new \DateTime(), [$process], 1.234, $timedOut);
    }

    /**
     * @return array<array{int, string, string}> Level, category and text of each logged message
     */
    private function getLoggedMessages(): array
    {
        return array_map(
            fn(array $message): array => [$message[1], $message[2], $message[0]],
            \Yii::getLogger()->messages
        );
    }
}
