<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Filters;

use Carbon\Carbon;
use Horat1us\Yii\Schedule\Contracts\FilterInterface;
use Horat1us\Yii\Schedule\Exceptions\InvalidFilterException;

/**
 * Filter that checks if the current time matches a specific datetime.
 *
 * Time is compared with minute precision (seconds are ignored).
 *
 * Example: new DateTime('2025-01-01 01:01') runs on January 1, 2025 at 1:01 AM
 */
readonly class DateTime implements FilterInterface
{
    private Carbon $targetDateTime;

    public function __construct(string $datetime)
    {
        try {
            $this->targetDateTime = Carbon::parse($datetime)->startOfMinute();
        } catch (\Throwable $e) {
            throw InvalidFilterException::invalidDateTime($datetime, $e);
        }
    }

    public function passes(\DateTimeInterface $dateTime): bool
    {
        $current = Carbon::instance($dateTime)->startOfMinute();
        return $current->equalTo($this->targetDateTime);
    }

    public function getDescription(): string
    {
        return "DateTime: {$this->targetDateTime->toDateTimeString()}";
    }

    public function getTargetDateTime(): Carbon
    {
        return $this->targetDateTime;
    }
}
