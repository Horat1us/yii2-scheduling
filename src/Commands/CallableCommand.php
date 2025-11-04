<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Commands;

use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Symfony\Component\Process\Process;

/**
 * Command that executes a PHP callable (array callable or named function).
 *
 * IMPORTANT: This command uses eval to execute code. Use with caution.
 * Closures are NOT supported because they cannot be serialized.
 *
 * Example: new CallableCommand([MyClass::class, 'method'])
 *          new CallableCommand('some_function')
 *
 * For closures, use ShellCommand with a script file instead.
 */
readonly class CallableCommand implements CommandInterface
{
    /**
     * @param callable $callable The callable to execute (array or string, no closures)
     * @param string|null $description Optional human-readable description
     */
    public function __construct(
        private mixed $callable,
        private ?string $description = null,
    ) {
    }

    public function toProcess(): Process
    {
        // Build the command string for array callables or function names
        if (is_array($this->callable)) {
            [$class, $method] = $this->callable;
            $className = is_object($class) ? get_class($class) : $class;
            $code = "call_user_func(['{$className}', '{$method}']);";
        } elseif (is_string($this->callable)) {
            $code = "call_user_func('{$this->callable}');";
        } else {
            throw new \InvalidArgumentException('CallableCommand only supports array callables and function names, not closures');
        }

        $command = sprintf('%s -r %s', PHP_BINARY, escapeshellarg($code));

        return Process::fromShellCommandline($command);
    }

    public function getDescription(): string
    {
        if ($this->description !== null) {
            return $this->description;
        }

        if ($this->callable instanceof \Closure) {
            return 'Closure command';
        }

        if (is_array($this->callable)) {
            [$class, $method] = $this->callable;
            $className = is_object($class) ? get_class($class) : $class;
            return "{$className}::{$method}";
        }

        return 'Callable command';
    }
}
