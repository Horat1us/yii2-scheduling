<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Fixtures;

use Horat1us\Yii\Schedule\Commands\YiiConsoleCommand;
use Horat1us\Yii\Schedule\Contracts\TaskProviderInterface;
use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\Tasks\Task;

/**
 * Mock task provider for testing.
 */
class MockTaskProvider implements TaskProviderInterface
{
    public function __construct(
        private readonly array $tasks = []
    ) {
    }

    public function getTasks(): array
    {
        if (!empty($this->tasks)) {
            return $this->tasks;
        }

        // Default tasks if none provided
        return [
            new Task(
                commands: new YiiConsoleCommand('cache/flush'),
                filters: new Cron('0 1 * * *'),
                description: 'Daily cache flush'
            ),
            new Task(
                commands: new YiiConsoleCommand('queue/process'),
                filters: new Cron('*/5 * * * *'),
                description: 'Process queue every 5 minutes'
            ),
        ];
    }
}
