<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Events;

use Horat1us\Yii\Schedule\Commands\ShellCommand;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\Tasks\Task;
use PHPUnit\Framework\TestCase;

class BeforeTaskEventTest extends TestCase
{
    public function testEventConstruction(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime('2025-01-01 12:00:00');

        $event = new BeforeTaskEvent($task, $scheduledTime);

        $this->assertSame($task, $event->task);
        $this->assertSame($scheduledTime, $event->scheduledTime);
    }

    public function testReadonlyProperties(): void
    {
        $task = new Task(commands: new ShellCommand('echo test'));
        $scheduledTime = new \DateTime('2025-01-01 12:00:00');

        $event = new BeforeTaskEvent($task, $scheduledTime);

        $reflection = new \ReflectionProperty($event, 'task');
        $this->assertTrue($reflection->isReadOnly());

        $reflection = new \ReflectionProperty($event, 'scheduledTime');
        $this->assertTrue($reflection->isReadOnly());
    }
}
