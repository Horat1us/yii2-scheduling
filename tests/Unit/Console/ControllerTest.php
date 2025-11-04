<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Console;

use Horat1us\Yii\Schedule\Console\Controller;
use Horat1us\Yii\Schedule\Console\RunAction;
use Horat1us\Yii\Schedule\Console\ListAction;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    private Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Controller('schedule', \Yii::$app);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Controller::class, $this->controller);
    }

    public function testDefaultActionIsList(): void
    {
        $this->assertSame('list', $this->controller->defaultAction);
    }

    public function testActionsAreConfigured(): void
    {
        $actions = $this->controller->actions();

        $this->assertArrayHasKey('run', $actions);
        $this->assertArrayHasKey('list', $actions);
        $this->assertSame(RunAction::class, $actions['run']);
        $this->assertSame(ListAction::class, $actions['list']);
    }

    public function testCurrentTimeDefaultsToNow(): void
    {
        $this->assertSame('now', $this->controller->currentTime);
    }

    public function testCurrentTimeCanBeSet(): void
    {
        $testTime = '2025-01-01 12:00:00';
        $this->controller->currentTime = $testTime;

        $this->assertSame($testTime, $this->controller->currentTime);
    }

    public function testOptionsIncludesCurrentTime(): void
    {
        $options = $this->controller->options('run');

        $this->assertContains('currentTime', $options);
    }

    public function testOptionsIncludesCurrentTimeForAllActions(): void
    {
        $runOptions = $this->controller->options('run');
        $listOptions = $this->controller->options('list');

        $this->assertContains('currentTime', $runOptions);
        $this->assertContains('currentTime', $listOptions);
    }

    public function testOptionAliasesIncludesCurrentTime(): void
    {
        $aliases = $this->controller->optionAliases();

        $this->assertArrayHasKey('t', $aliases);
        $this->assertSame('currentTime', $aliases['t']);
    }
}
