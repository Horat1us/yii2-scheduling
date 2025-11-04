<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Contracts;

use Symfony\Component\Process\Process;

/**
 * Defines the contract for task commands.
 *
 * Commands represent the actual work to be executed when a task runs.
 * Each command can be converted to a Symfony Process for execution.
 */
interface CommandInterface
{
    /**
     * Convert this command to a Symfony Process.
     *
     * The process should be ready to start but not yet started.
     *
     * @return Process The process representing this command
     */
    public function toProcess(): Process;

    /**
     * Get a human-readable description of this command.
     *
     * Used for logging and display purposes.
     *
     * @return string Description of what this command does
     */
    public function getDescription(): string;
}
