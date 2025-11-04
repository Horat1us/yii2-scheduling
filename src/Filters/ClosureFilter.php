<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Filters;

use Horat1us\Yii\Schedule\Contracts\FilterInterface;

/**
 * Filter that evaluates a closure to determine if a task should run.
 *
 * The closure receives the current datetime and should return a boolean.
 *
 * Example: new ClosureFilter(fn(\DateTimeInterface $dt) => $dt->format('H') === '01')
 *          runs only during the 1 AM hour
 */
readonly class ClosureFilter implements FilterInterface
{
    /**
     * @param \Closure(\DateTimeInterface): bool $closure
     * @param string|null $description Optional human-readable description
     */
    public function __construct(
        private \Closure $closure,
        private ?string $description = null,
    ) {
    }

    public function passes(\DateTimeInterface $dateTime): bool
    {
        return (bool)($this->closure)($dateTime);
    }

    public function getDescription(): string
    {
        return $this->description ?? 'Custom closure filter';
    }
}
