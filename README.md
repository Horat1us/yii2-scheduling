# Yii2 Scheduling Package

[![CI](https://github.com/horat1us/yii2-scheduling/workflows/CI/badge.svg)](https://github.com/horat1us/yii2-scheduling/actions)
[![codecov](https://codecov.io/gh/horat1us/yii2-scheduling/branch/master/graph/badge.svg)](https://codecov.io/gh/horat1us/yii2-scheduling)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A modern, flexible task scheduling library for Yii2 applications using PHP 8.4+ features.

## Features

- **Provider Pattern**: Lazy-load tasks only when scheduling is triggered
- **Multiple Commands**: Tasks can execute multiple commands in parallel
- **Flexible Filters**: Cron expressions, fixed datetime, custom closures, environment checks
- **Event-Driven**: Task lifecycle events with built-in logging behavior
- **SOLID Design**: Separated concerns with services, plain data objects, and dependency injection
- **Modern PHP**: Readonly classes, constructor property promotion, typed properties

## Requirements

- PHP 8.4+
- Yii2 2.0.52+
- nesbot/carbon ^3.8.4
- symfony/process ^6.0 || ^7.0
- dragonmantank/cron-expression ^3.6

## Installation

```bash
composer require horat1us/yii2-scheduling
```

## Configuration

### 1. Bootstrap the Package

Add the bootstrap class to your console application configuration:

```php
// config/console.php
return [
    'bootstrap' => [
        Horat1us\Yii\Schedule\Bootstrap::class,
    ],
    // ... other config
];
```

### 2. Configure Tasks

Create a bootstrap class in your application to configure scheduled tasks:

```php
<?php

namespace app\bootstrap;

use Horat1us\Yii\Schedule;
use yii\base\BootstrapInterface;

class ScheduleBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $manager = \Yii::$container->get(Schedule\ScheduleManager::class);

        // Option 1: Add tasks via a provider (recommended for many tasks)
        $manager->addProvider(new MyTaskProvider());

        // Option 2: Add individual tasks directly
        $manager->addTask(
            new Schedule\Tasks\Task(
                commands: new Schedule\Commands\YiiConsoleCommand('cache/flush'),
                filters: new Schedule\Filters\Cron('0 1 * * *'),
                timeout: 300,
                description: 'Daily cache flush'
            )
        );
    }
}
```

Then register your bootstrap in the configuration:

```php
// config/console.php
return [
    'bootstrap' => [
        Horat1us\Yii\Schedule\Bootstrap::class,
        app\bootstrap\ScheduleBootstrap::class, // Your tasks
    ],
];
```

## Usage

### Basic Task Creation

```php
use Horat1us\Yii\Schedule;

$task = new Schedule\Tasks\Task(
    commands: new Schedule\Commands\YiiConsoleCommand('migrate/up', ['--interactive' => 0]),
    filters: new Schedule\Filters\Cron('0 2 * * *'),
    timeout: 600,
    description: 'Run migrations daily at 2 AM'
);
```

### Multiple Commands (Parallel Execution)

```php
$task = new Schedule\Tasks\Task(
    commands: [
        new Schedule\Commands\YiiConsoleCommand('cache/flush'),
        new Schedule\Commands\YiiConsoleCommand('queue/process'),
        new Schedule\Commands\ShellCommand('curl https://example.com/ping'),
    ],
    filters: new Schedule\Filters\Cron('*/5 * * * *'), // Every 5 minutes
    description: 'Maintenance tasks'
);
```

### Filter Types

#### Cron Expression

```php
use Horat1us\Yii\Schedule\Filters\Cron;

$filter = new Cron('0 1 * * *'); // Daily at 1 AM
$filter = new Cron('*/15 * * * *'); // Every 15 minutes
$filter = new Cron('0 9-17 * * 1-5'); // Every hour 9 AM-5 PM, Monday-Friday
```

#### Fixed DateTime

```php
use Horat1us\Yii\Schedule\Filters\DateTime;

$filter = new DateTime('2025-12-31 23:59'); // One-time task
```

#### Environment Check

```php
use Horat1us\Yii\Schedule\Filters\Environment;

$filter = new Environment('prod'); // Only in production
$filter = new Environment(['prod', 'staging']); // Multiple environments
```

#### Custom Closure

```php
use Horat1us\Yii\Schedule\Filters\ClosureFilter;

$filter = new ClosureFilter(
    fn(\DateTimeInterface $dt) => $dt->format('H') === '01',
    description: 'Only during 1 AM hour'
);
```

#### Boolean/Constant

```php
use Horat1us\Yii\Schedule\Filters\BooleanFilter;

$filter = new BooleanFilter(YII_ENV_PROD, 'Production only');
$filter = new BooleanFilter(false); // Disable task
```

### Multiple Filters (AND Logic)

All filters must pass for a task to run:

```php
$task = new Schedule\Tasks\Task(
    commands: new Schedule\Commands\YiiConsoleCommand('report/generate'),
    filters: [
        new Schedule\Filters\Cron('0 6 * * 1'), // Monday at 6 AM
        new Schedule\Filters\Environment('prod'), // AND production environment
        new Schedule\Filters\ClosureFilter(fn() => date('d') <= 7), // AND first week of month
    ],
    description: 'Weekly report (first Monday of month, production only)'
);
```

### Task Providers

For lazy-loading tasks (recommended for many tasks or database-driven schedules):

```php
use Horat1us\Yii\Schedule\Contracts\TaskProviderInterface;
use Horat1us\Yii\Schedule\Contracts\TaskInterface;

class DatabaseTaskProvider implements TaskProviderInterface
{
    public function getTasks(): array
    {
        // Load tasks from database or any source
        $records = ScheduledTask::find()->where(['active' => true])->all();

        return array_map(function ($record) {
            return new Schedule\Tasks\Task(
                commands: new Schedule\Commands\YiiConsoleCommand($record->route),
                filters: new Schedule\Filters\Cron($record->cron),
                timeout: $record->timeout,
                description: $record->description
            );
        }, $records);
    }
}

// In bootstrap
$manager->addProvider(new DatabaseTaskProvider());
```

### Command Types

#### Yii Console Command

```php
new Schedule\Commands\YiiConsoleCommand(
    route: 'migrate/up',
    options: ['--interactive' => 0, '--compact' => true],
    yiiExecutable: './yii' // optional, defaults to './yii'
);
```

#### Shell Command

```php
new Schedule\Commands\ShellCommand('php artisan queue:work --once');
new Schedule\Commands\ShellCommand('curl -X POST https://example.com/webhook');
```

## Console Commands

### Run Due Tasks

```bash
# Run all tasks that are due now
php yii schedule/run

# Run tasks for a specific time (useful for testing or running missed schedules)
php yii schedule/run --currentTime="2025-01-01 12:00:00"
php yii schedule/run -t "2025-01-01 12:00:00"
```

### List All Tasks

```bash
# List all scheduled tasks (shows current status)
php yii schedule/list

# List tasks for a specific time (useful for testing what would run at that time)
php yii schedule/list --currentTime="2025-01-01 12:00:00"
php yii schedule/list -t "2025-01-01 12:00:00"
```

**Note**: The `--currentTime` option (or `-t` alias) can be used with both `run` and `list` commands. This is useful for:
- Testing scheduled tasks on production without waiting for the actual time
- Running missed schedules after downtime
- Verifying task configuration for specific times

Example output:
```
Scheduled Tasks (current time: 2025-01-04 10:30:00)

[1] Daily cache flush [DUE]
    Commands: 1
    Timeout: 300s
    Filters: 1
      - Cron: 0 1 * * *
        Next: 2025-01-05 01:00:00 | Prev: 2025-01-04 01:00:00
      → yii cache/flush

[2] Maintenance tasks [PENDING]
    Commands: 3
    Timeout: 600s
    Filters: 1
      - Cron: */5 * * * *
        Next: 2025-01-04 10:35:00 | Prev: 2025-01-04 10:30:00
      → yii cache/flush
      → yii queue/process
      → curl https://example.com/ping
```

## Events and Logging

The package provides events for task lifecycle:

```php
use Horat1us\Yii\Schedule\TaskRunner;
use Horat1us\Yii\Schedule\Events\BeforeTaskEvent;
use Horat1us\Yii\Schedule\Events\AfterTaskEvent;

$runner = \Yii::$container->get(TaskRunner::class);

$runner->on(TaskRunner::EVENT_BEFORE_TASK, function (BeforeTaskEvent $event) {
    echo "Starting: " . $event->task->getDescription() . "\n";
});

$runner->on(TaskRunner::EVENT_AFTER_TASK, function (AfterTaskEvent $event) {
    if ($event->isSuccessful()) {
        echo "Success! Took {$event->executionTime}s\n";
    } else {
        echo "Failed: " . $event->getErrorOutput() . "\n";
    }
});
```

### Built-in Logging Behavior

Logging is automatically enabled via `LoggingBehavior` (uses Yii2 logger):

```php
// Logs are written to 'schedule' category
// Configure in your log component:
'log' => [
    'targets' => [
        [
            'class' => yii\log\FileTarget::class,
            'categories' => ['schedule'],
            'logFile' => '@runtime/logs/schedule.log',
            'levels' => ['info', 'error'],
        ],
    ],
],
```

To disable logging:

```php
// In config
'container' => [
    'definitions' => [
        Horat1us\Yii\Schedule\Bootstrap::class => [
            'enableLogging' => false,
        ],
    ],
],
```

## Cron Setup

Add this to your crontab to check for due tasks every minute:

```cron
* * * * * cd /path/to/project && php yii schedule/run >> /dev/null 2>&1
```

## Advanced Usage

### Custom Task Implementation

You can create custom task implementations without inheritance:

```php
use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Horat1us\Yii\Schedule\Contracts\FilterInterface;

readonly class MyCustomTask implements TaskInterface
{
    public function __construct(
        private string $userId,
    ) {}

    public function getCommands(): array
    {
        return [
            new YiiConsoleCommand('user/notify', ['userId' => $this->userId]),
        ];
    }

    public function getFilters(): array
    {
        return [new Cron('0 9 * * *')];
    }

    public function getTimeout(): int
    {
        return 300;
    }

    public function getDescription(): ?string
    {
        return "Notify user {$this->userId}";
    }
}
```

### Custom Filters

```php
use Horat1us\Yii\Schedule\Contracts\FilterInterface;

readonly class BusinessDaysFilter implements FilterInterface
{
    public function passes(\DateTimeInterface $dateTime): bool
    {
        $dayOfWeek = (int)$dateTime->format('N');
        return $dayOfWeek >= 1 && $dayOfWeek <= 5; // Monday-Friday
    }

    public function getDescription(): string
    {
        return 'Business days only (Mon-Fri)';
    }
}
```

## Testing

The package includes comprehensive PHPUnit tests with custom Yii2 bootstrap (no standard Yii2 test framework required).

### Run All Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

### Run Specific Test Suites

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite=Unit

# Integration tests only
./vendor/bin/phpunit --testsuite=Integration
```

### Run Specific Test Classes

```bash
./vendor/bin/phpunit tests/Unit/Filters/CronTest.php
```

### Test Coverage

Tests cover:
- ✅ All filter types (Cron, DateTime, Closure, Boolean, Environment)
- ✅ All command types (YiiConsoleCommand, ShellCommand)
- ✅ Task creation and validation
- ✅ TaskEvaluator business logic (AND filter logic)
- ✅ ScheduleManager (providers, caching, singleton)
- ✅ Event system (BeforeTaskEvent, AfterTaskEvent)
- ✅ Bootstrap integration and DI container setup

Coverage reports are automatically uploaded to [Codecov](https://codecov.io/gh/horat1us/yii2-scheduling).

## Code Style

```bash
composer lint
composer phpcbf
```

## Continuous Integration

This package uses GitHub Actions for continuous integration:

### Automated Checks

Every push and pull request triggers:
- ✅ **Code Style Check** - PSR-12 compliance validation
- ✅ **Unit Tests** - Full test suite execution
- ✅ **Integration Tests** - Bootstrap and DI container tests
- ✅ **Code Coverage** - Coverage reports uploaded to Codecov

### Multi-Version Support

Tests run on:
- PHP 8.4 (current)
- More versions can be added to the matrix as needed

### Workflow Features

- **Dependency Caching** - Composer dependencies cached between runs
- **Coverage Reporting** - Automatic coverage upload to Codecov
- **Parallel Jobs** - Linting and testing run in parallel
- **Fast Feedback** - Using PCOV for fast coverage generation

See the [CI workflow](.github/workflows/ci.yml) for details.

## Architecture

### Components

- **ScheduleManager**: Singleton component managing task providers and static tasks
- **TaskRunner**: Component executing tasks with event support
- **TaskEvaluator**: Service evaluating task filters (business logic)
- **Task**: Plain data object holding task configuration
- **Filters**: Determine when tasks should run (Cron, DateTime, Closure, Boolean, Environment)
- **Commands**: Define what to execute (YiiConsoleCommand, ShellCommand)
- **Events**: BeforeTaskEvent, AfterTaskEvent
- **LoggingBehavior**: Yii2 behavior for automatic logging

### Design Principles

- **SOLID**: Single responsibility, dependency injection, interface segregation
- **DRY**: Reusable filters, commands, and providers
- **KISS**: Simple, clear APIs with sensible defaults
- **Separation of Concerns**: Data objects vs. business logic vs. UI

## Migration from Legacy Code

If migrating from the old module structure:

1. Update namespace from `Wearesho\Bobra\App\Scheduling` to `Horat1us\Yii\Schedule`
2. Replace hard-coded task arrays with providers or `addTask()` calls
3. Update task configuration from arrays to Task objects
4. Replace `'schedule' => '0 1 * * *'` with `filters: new Cron('0 1 * * *')`
5. Replace `'route' => 'command/action'` with `commands: new YiiConsoleCommand('command/action')`

## License

This project is open-sourced software.

## Support

For issues and feature requests, please use the GitHub issue tracker.
