<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule;

use Horat1us\Yii\Schedule\Behaviors\LoggingBehavior;
use Horat1us\Yii\Schedule\Console\Controller;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;

/**
 * Bootstrap class for the scheduling package.
 *
 * This class automatically:
 * - Registers the schedule console controller
 * - Configures ScheduleManager as a singleton in the DI container
 * - Attaches LoggingBehavior to TaskRunner
 *
 * Usage in config/console.php:
 * ```php
 * return [
 *     'bootstrap' => [
 *         Horat1us\Yii\Schedule\Bootstrap::class,
 *     ],
 *     // ... other config
 * ];
 * ```
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @var string The controller map key for the schedule controller
     */
    public string $controllerMapKey = 'schedule';

    /**
     * @var bool Whether to attach LoggingBehavior to TaskRunner
     */
    public bool $enableLogging = true;

    public function bootstrap($app): void
    {
        // Only register controller in console applications
        if ($app instanceof ConsoleApplication && !array_key_exists($this->controllerMapKey, $app->controllerMap)) {
            $app->controllerMap[$this->controllerMapKey] = Controller::class;
        }

        // Register ScheduleManager as singleton
        \Yii::$container->setSingleton(ScheduleManager::class);

        // Register TaskRunner with optional logging behavior
        \Yii::$container->setSingleton(TaskRunner::class, function () {
            $runner = new TaskRunner();

            if ($this->enableLogging) {
                $runner->attachBehavior('logging', LoggingBehavior::class);
            }

            return $runner;
        });

        // Register TaskEvaluator
        \Yii::$container->setSingleton(TaskEvaluator::class);
    }
}
