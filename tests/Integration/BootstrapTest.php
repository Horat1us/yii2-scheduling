<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Integration;

use Horat1us\Yii\Schedule\Bootstrap;
use Horat1us\Yii\Schedule\Console\Controller;
use Horat1us\Yii\Schedule\ScheduleManager;
use Horat1us\Yii\Schedule\TaskRunner;
use Horat1us\Yii\Schedule\TaskEvaluator;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

class BootstrapTest extends TestCase
{
    private ?Application $app = null;

    protected function setUp(): void
    {
        // Clear DI container before each test
        \Yii::$container = new \yii\di\Container();
    }

    protected function tearDown(): void
    {
        // Properly clean up the application and restore handlers
        $this->destroyApplication();

        // Clear DI container
        \Yii::$container = new \yii\di\Container();
    }

    private function createApplication(): Application
    {
        $config = [
            'id' => 'test-app',
            'basePath' => dirname(__DIR__, 2),
            'components' => [
                'log' => [
                    'traceLevel' => 0,
                    'targets' => [],
                ],
            ],
        ];

        $this->app = new Application($config);
        return $this->app;
    }

    private function destroyApplication(): void
    {
        if ($this->app !== null) {
            // Remove Yii2's error handler
            restore_error_handler();
            restore_exception_handler();

            $this->app = null;
        }
    }

    public function testBootstrapRegistersController(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $this->assertArrayHasKey('schedule', $app->controllerMap);
        $this->assertSame(Controller::class, $app->controllerMap['schedule']);
    }

    public function testBootstrapWithCustomControllerMapKey(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->controllerMapKey = 'custom-schedule';
        $bootstrap->bootstrap($app);

        $this->assertArrayHasKey('custom-schedule', $app->controllerMap);
    }

    public function testBootstrapDoesNotOverrideExistingController(): void
    {
        $app = $this->createApplication();
        $app->controllerMap['schedule'] = 'custom\Controller';

        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $this->assertSame('custom\Controller', $app->controllerMap['schedule']);
    }

    public function testBootstrapRegistersScheduleManagerAsSingleton(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $manager1 = \Yii::$container->get(ScheduleManager::class);
        $manager2 = \Yii::$container->get(ScheduleManager::class);

        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(ScheduleManager::class, $manager1);
    }

    public function testBootstrapRegistersTaskRunnerAsSingleton(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $runner1 = \Yii::$container->get(TaskRunner::class);
        $runner2 = \Yii::$container->get(TaskRunner::class);

        $this->assertSame($runner1, $runner2);
        $this->assertInstanceOf(TaskRunner::class, $runner1);
    }

    public function testBootstrapRegistersTaskEvaluatorAsSingleton(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $evaluator1 = \Yii::$container->get(TaskEvaluator::class);
        $evaluator2 = \Yii::$container->get(TaskEvaluator::class);

        $this->assertSame($evaluator1, $evaluator2);
        $this->assertInstanceOf(TaskEvaluator::class, $evaluator1);
    }

    public function testBootstrapAttachesLoggingBehaviorByDefault(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($app);

        $runner = \Yii::$container->get(TaskRunner::class);

        $this->assertArrayHasKey('logging', $runner->getBehaviors());
    }

    public function testBootstrapCanDisableLogging(): void
    {
        $app = $this->createApplication();
        $bootstrap = new Bootstrap();
        $bootstrap->enableLogging = false;
        $bootstrap->bootstrap($app);

        $runner = \Yii::$container->get(TaskRunner::class);

        $this->assertArrayNotHasKey('logging', $runner->getBehaviors());
    }

    public function testBootstrapOnlyRegistersControllerInConsoleApp(): void
    {
        // Create a web application
        $webConfig = [
            'id' => 'test-web-app',
            'basePath' => dirname(__DIR__, 2),
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/',
                ],
            ],
        ];

        $webApp = new \yii\web\Application($webConfig);

        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($webApp);

        $this->assertArrayNotHasKey('schedule', $webApp->controllerMap);

        // Clean up web app handlers
        restore_error_handler();
        restore_exception_handler();
    }
}
