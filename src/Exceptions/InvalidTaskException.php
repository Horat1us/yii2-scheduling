<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Exceptions;

/**
 * Exception thrown when a task configuration is invalid.
 */
class InvalidTaskException extends ScheduleException
{
    public static function emptyCommands(): self
    {
        return new self('Task must have at least one command');
    }

    public static function invalidTimeout(int $timeout): self
    {
        return new self("Task timeout must be positive, got: {$timeout}");
    }

    public static function invalidCommand(mixed $command): self
    {
        $type = get_debug_type($command);
        return new self("Invalid command type: {$type}");
    }
}
