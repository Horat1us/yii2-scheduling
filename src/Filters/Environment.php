<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Filters;

use Horat1us\Yii\Schedule\Contracts\FilterInterface;

/**
 * Filter that checks if the current environment matches expected value(s).
 *
 * Example: new Environment('prod') runs only in production
 *          new Environment(['prod', 'staging']) runs in production or staging
 */
readonly class Environment implements FilterInterface
{
    /**
     * @param string|string[] $allowedEnvironments
     */
    public function __construct(
        private string|array $allowedEnvironments,
    ) {
    }

    public function passes(\DateTimeInterface $dateTime): bool
    {
        $currentEnv = YII_ENV;
        $allowed = (array)$this->allowedEnvironments;

        return in_array($currentEnv, $allowed, true);
    }

    public function getDescription(): string
    {
        $environments = implode(', ', (array)$this->allowedEnvironments);
        return "Environment: {$environments}";
    }
}
