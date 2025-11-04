<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Exceptions;

/**
 * Exception thrown when a filter configuration is invalid.
 */
class InvalidFilterException extends ScheduleException
{
    public static function invalidCronExpression(string $expression, \Throwable $previous): self
    {
        return new self(
            "Invalid cron expression: {$expression}",
            previous: $previous
        );
    }

    public static function invalidDateTime(string $datetime, \Throwable $previous): self
    {
        return new self(
            "Invalid datetime: {$datetime}",
            previous: $previous
        );
    }

    public static function invalidFilter(mixed $filter): self
    {
        $type = get_debug_type($filter);
        return new self("Invalid filter type: {$type}");
    }
}
