<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Console;

use Horat1us\Yii\Schedule\Console\Controller;
use Horat1us\Yii\Schedule\Console\ListAction;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\TaskEvaluator;
use PHPUnit\Framework\TestCase;
use yii\console\Controller as BaseController;
use yii\console\ExitCode;

class ListActionTest extends TestCase
{
    private BaseController $controller;
    private ScheduleManager $scheduleManager;
    private TaskEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock controller
        $this->controller = $this->createMock(Controller::class);

        // Create mock schedule manager
        $this->scheduleManager = $this->createMock(ScheduleManager::class);

        // Create mock task evaluator
        $this->evaluator = $this->createMock(TaskEvaluator::class);
    }

    public function testCanBeInstantiated(): void
    {
        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $this->assertInstanceOf(ListAction::class, $action);
    }

    public function testRunWithNoTasks(): void
    {
        $this->scheduleManager
            ->expects($this->once())
            ->method('getTasks')
            ->willReturn([]);

        $this->controller
            ->expects($this->once())
            ->method('stdout')
            ->with(
                $this->stringContains('No scheduled tasks'),
                $this->anything()
            );

        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $exitCode = $action->run();

        $this->assertSame(ExitCode::OK, $exitCode);
    }

    public function testRunWithTasks(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Test Task');
        $task->method('getCommands')->willReturn([]);
        $task->method('getFilters')->willReturn([]);
        $task->method('getTimeout')->willReturn(60);

        $this->scheduleManager
            ->expects($this->once())
            ->method('getTasks')
            ->willReturn([$task]);

        $this->evaluator
            ->expects($this->once())
            ->method('isDue')
            ->willReturn(true);

        $this->controller
            ->expects($this->atLeastOnce())
            ->method('stdout');

        $this->controller
            ->expects($this->atLeastOnce())
            ->method('ansiFormat')
            ->willReturnCallback(fn($text) => $text);

        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $exitCode = $action->run();

        $this->assertSame(ExitCode::OK, $exitCode);
    }

    public function testDisplaysTaskAsDue(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Due Task');
        $task->method('getCommands')->willReturn([]);
        $task->method('getFilters')->willReturn([]);
        $task->method('getTimeout')->willReturn(60);

        $this->scheduleManager
            ->method('getTasks')
            ->willReturn([$task]);

        $this->evaluator
            ->method('isDue')
            ->willReturn(true);

        $this->controller
            ->expects($this->atLeastOnce())
            ->method('ansiFormat')
            ->with('DUE', $this->anything())
            ->willReturn('DUE');

        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $action->run();
    }

    public function testDisplaysTaskAsPending(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Pending Task');
        $task->method('getCommands')->willReturn([]);
        $task->method('getFilters')->willReturn([]);
        $task->method('getTimeout')->willReturn(60);

        $this->scheduleManager
            ->method('getTasks')
            ->willReturn([$task]);

        $this->evaluator
            ->method('isDue')
            ->willReturn(false);

        $this->controller
            ->expects($this->atLeastOnce())
            ->method('ansiFormat')
            ->with('PENDING', $this->anything())
            ->willReturn('PENDING');

        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $action->run();
    }

    public function testDisplaysTaskWithCronFilter(): void
    {
        $cronFilter = $this->createMock(Cron::class);
        $cronFilter->method('getDescription')->willReturn('0 0 * * *');
        $cronFilter->method('getNextRunDate')->willReturn(new \DateTime('2025-01-01 00:00:00'));
        $cronFilter->method('getPreviousRunDate')->willReturn(new \DateTime('2024-12-31 00:00:00'));

        $task = $this->createMock(TaskInterface::class);
        $task->method('getDescription')->willReturn('Cron Task');
        $task->method('getCommands')->willReturn([]);
        $task->method('getFilters')->willReturn([$cronFilter]);
        $task->method('getTimeout')->willReturn(60);

        $this->scheduleManager
            ->method('getTasks')
            ->willReturn([$task]);

        $this->evaluator
            ->method('isDue')
            ->willReturn(false);

        $this->controller
            ->method('ansiFormat')
            ->willReturnCallback(fn($text) => $text);

        $action = new ListAction(
            'list',
            $this->controller,
            $this->scheduleManager,
            $this->evaluator
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
            ->onlyMethods(['stdout', 'ansiFormat'])
            ->getMock();

        $controller->currentTime = $testTime;

        $this->scheduleManager
            ->method('getTasks')
            ->willReturn([]);

        $controller
            ->expects($this->once())
            ->method('stdout')
            ->with(
                $this->stringContains('No scheduled tasks'),
                $this->anything()
            );

        $action = new ListAction(
            'list',
            $controller,
            $this->scheduleManager,
            $this->evaluator
        );

        $action->run();

        // Verify that the controller's currentTime was used
        $this->assertSame($testTime, $controller->currentTime);
    }
}
