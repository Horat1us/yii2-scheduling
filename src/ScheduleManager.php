<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule;

use Horat1us\Yii\Schedule\Contracts\TaskInterface;
use Horat1us\Yii\Schedule\Contracts\TaskProviderInterface;
use yii\base\Component;

/**
 * ScheduleManager is a Yii2 Component that manages scheduled tasks.
 *
 * It acts as a configuration registry for tasks and providers.
 * Register as a singleton in the DI container for application-wide access.
 *
 * Usage:
 * ```php
 * $manager = \Yii::$container->get(ScheduleManager::class);
 * $manager->addProvider(new MyTaskProvider());
 * $manager->addTask(new Task(...));
 * ```
 */
class ScheduleManager extends Component
{
    /** @var TaskProviderInterface[] */
    private array $providers = [];

    /** @var TaskInterface[] */
    private array $staticTasks = [];

    /** @var TaskInterface[]|null Cached list of all tasks */
    private ?array $cachedTasks = null;

    private TaskEvaluator $evaluator;

    public function __construct(TaskEvaluator $evaluator = null, $config = [])
    {
        parent::__construct($config);
        $this->evaluator = $evaluator ?? new TaskEvaluator();
    }

    /**
     * Add a task provider.
     *
     * Providers are lazily evaluated when getTasks() is called.
     *
     * @param TaskProviderInterface $provider
     * @return void
     */
    public function addProvider(TaskProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->cachedTasks = null; // Invalidate cache
    }

    /**
     * Add a static task.
     *
     * Useful for one-off task definitions without creating a provider.
     *
     * @param TaskInterface $task
     * @return void
     */
    public function addTask(TaskInterface $task): void
    {
        $this->staticTasks[] = $task;
        $this->cachedTasks = null; // Invalidate cache
    }

    /**
     * Get all tasks from providers and static tasks.
     *
     * Lazily loads tasks from providers on first call and caches the result.
     *
     * @return TaskInterface[]
     */
    public function getTasks(): array
    {
        if ($this->cachedTasks !== null) {
            return $this->cachedTasks;
        }

        $tasks = $this->staticTasks;

        foreach ($this->providers as $provider) {
            $tasks = array_merge($tasks, $provider->getTasks());
        }

        $this->cachedTasks = $tasks;
        return $tasks;
    }

    /**
     * Get tasks that are due to run at the specified time.
     *
     * @param \DateTimeInterface $dateTime The time to check against
     * @return TaskInterface[]
     */
    public function getDueTasks(\DateTimeInterface $dateTime): array
    {
        return $this->evaluator->filterDueTasks($this->getTasks(), $dateTime);
    }

    /**
     * Clear cached tasks.
     *
     * Use this if you need to force re-evaluation of providers.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cachedTasks = null;
    }
}
