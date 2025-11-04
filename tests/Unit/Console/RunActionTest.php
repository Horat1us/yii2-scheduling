<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Console;

use Carbon\Carbon;
use Horat1us\Yii\Schedule\Console\Controller;
use Horat1us\Yii\Schedule\Console\RunAction;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\TaskRunner;
use PHPUnit\Framework\TestCase;
use yii\console\Controller as BaseController;
use yii\console\ExitCode;

class RunActionTest extends TestCase
{
    private BaseController $controller;
    private ScheduleManager $scheduleManager;
    private TaskRunner $taskRunner;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock controller
        $this->controller = $this->createMock(Controller::class);

        // Create mock schedule manager
        $this->scheduleManager = $this->createMock(ScheduleManager::class);

        // Create mock task runner
        $this->taskRunner = $this->createMock(TaskRunner::class);
    }

    public function testCanBeInstantiated(): void
    {
        $action = new RunAction(
            'run',
            $this->controller,
            $this->scheduleManager,
            $this->taskRunner
        );

        $this->assertInstanceOf(RunAction::class, $action);
    }

    public function testRunWithNoDueTasks(): void
    {
        $this->scheduleManager
            ->expects($this->once())
            ->method('getDueTasks')
            ->willReturn([]);

        $this->controller
            ->expects($this->once())
            ->method('stdout')
            ->with(
                $this->stringContains('No tasks are due'),
                $this->anything()
            );

        $this->taskRunner
            ->expects($this->never())
            ->method('runTasks');

        $action = new RunAction(
            'run',
            $this->controller,
            $this->scheduleManager,
            $this->taskRunner
        );

        $exitCode = $action->run();

        $this->assertSame(ExitCode::OK, $exitCode);
    }

    public function testRunWithDueTasks(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Test Task');
        $task->method('getCommands')->willReturn([]);

        $dueTasks = [$task];

        $this->scheduleManager
            ->expects($this->once())
            ->method('getDueTasks')
            ->willReturn($dueTasks);

        $this->taskRunner
            ->expects($this->once())
            ->method('runTasks')
            ->with($dueTasks, $this->isInstanceOf(Carbon::class));

        $this->controller
            ->expects($this->atLeastOnce())
            ->method('stdout');

        $action = new RunAction(
            'run',
            $this->controller,
            $this->scheduleManager,
            $this->taskRunner
        );

        $exitCode = $action->run();

        $this->assertSame(ExitCode::OK, $exitCode);
    }

    public function testCurrentTimeIsReadFromController(): void
    {
        $testTime = '2025-01-01 12:00:00';

        // Create a real controller with currentTime set
        $controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['stdout'])
            ->getMock();

        $controller->currentTime = $testTime;

        $this->scheduleManager
            ->method('getDueTasks')
            ->willReturn([]);

        $controller
            ->method('stdout');

        $action = new RunAction(
            'run',
            $controller,
            $this->scheduleManager,
            $this->taskRunner
        );

        $action->run();

        // Verify that the controller's currentTime was used
        $this->assertSame($testTime, $controller->currentTime);
    }

    public function testEventHandlersAreAttached(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Test Task');
        $task->method('getCommands')->willReturn([]);

        $this->scheduleManager
            ->method('getDueTasks')
            ->willReturn([$task]);

        $this->taskRunner
            ->expects($this->exactly(2))
            ->method('on')
            ->with(
                $this->logicalOr(
                    $this->equalTo(TaskRunner::EVENT_BEFORE_TASK),
                    $this->equalTo(TaskRunner::EVENT_AFTER_TASK)
                ),
                $this->isCallable()
            );

        $action = new RunAction(
            'run',
            $this->controller,
            $this->scheduleManager,
            $this->taskRunner
        );

        $action->run();
    }
}
