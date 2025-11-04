<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Events;

use Horat1us\Yii\Schedule\Commands\ShellCommand;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Tasks\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class AfterTaskEventTest extends TestCase
{
    public function testEventConstruction(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime('2025-01-01 12:00:00');
        $processes = [];
        $executionTime = 1.23;

        $event = new AfterTaskEvent($task, $scheduledTime, $processes, $executionTime);

        $this->assertSame($task, $event->task);
        $this->assertSame($scheduledTime, $event->scheduledTime);
        $this->assertSame($processes, $event->processes);
        $this->assertSame(1.23, $event->executionTime);
    }

    public function testIsSuccessfulWithAllSuccessfulProcesses(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('isSuccessful')->willReturn(true);

        $process2 = $this->createMock(Process::class);
        $process2->method('isSuccessful')->willReturn(true);

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertTrue($event->isSuccessful());
    }

    public function testIsNotSuccessfulWithOneFailedProcess(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('isSuccessful')->willReturn(true);

        $process2 = $this->createMock(Process::class);
        $process2->method('isSuccessful')->willReturn(false);

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertFalse($event->isSuccessful());
    }

    public function testGetOutputCombinesAllProcessOutputs(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('getOutput')->willReturn('Output 1');

        $process2 = $this->createMock(Process::class);
        $process2->method('getOutput')->willReturn('Output 2');

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertSame("Output 1\nOutput 2", $event->getOutput());
    }

    public function testGetOutputIgnoresEmptyOutputs(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('getOutput')->willReturn('Output 1');

        $process2 = $this->createMock(Process::class);
        $process2->method('getOutput')->willReturn('   '); // Whitespace only

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertSame('Output 1', $event->getOutput());
    }

    public function testGetErrorOutputCombinesAllProcessErrorOutputs(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('getErrorOutput')->willReturn('Error 1');

        $process2 = $this->createMock(Process::class);
        $process2->method('getErrorOutput')->willReturn('Error 2');

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertSame("Error 1\nError 2", $event->getErrorOutput());
    }

    public function testGetErrorOutputIgnoresEmptyOutputs(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime();

        $process1 = $this->createMock(Process::class);
        $process1->method('getErrorOutput')->willReturn('');

        $process2 = $this->createMock(Process::class);
        $process2->method('getErrorOutput')->willReturn('Error 2');

        $event = new AfterTaskEvent($task, $scheduledTime, [$process1, $process2], 1.0);

        $this->assertSame('Error 2', $event->getErrorOutput());
    }
}
