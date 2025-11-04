<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Filters;

use Horat1us\Yii\Schedule\Contracts\FilterInterface;

/**
 * Filter that always returns a fixed boolean value.
 *
 * Useful for quickly enabling/disabling tasks or for conditional compilation.
 *
 * Example: new BooleanFilter(YII_ENV_PROD) only runs in production
 *          new BooleanFilter(false) disables the task
 */
readonly class BooleanFilter implements FilterInterface
{
    public function __construct(
        private bool $value,
        private ?string $description = null,
    ) {
    }

    public function passes(\DateTimeInterface $dateTime): bool
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        if ($this->description !== null) {
            return $this->description;
        }

        return $this->value ? 'Enabled' : 'Disabled';
    }
}
