<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Commands;

use Horat1us\Yii\Schedule\Contracts\CommandInterface;
use Symfony\Component\Process\Process;

/**
 * Command that executes a PHP callable (closure or function).
 *
 * The callable is serialized and executed in a separate PHP process.
 * Note: The callable must be serializable (no closure over $this or variables from outer scope).
 *
 * Example: new CallableCommand(fn() => file_put_contents('/tmp/test.txt', 'Hello'))
 */
readonly class CallableCommand implements CommandInterface
{
    /**
     * @param callable $callable The callable to execute
     * @param string|null $description Optional human-readable description
     */
    public function __construct(
        private mixed $callable,
        private ?string $description = null,
    ) {
    }

    public function toProcess(): Process
    {
        $serialized = base64_encode(serialize($this->callable));
        $command = sprintf(
            '%s -r %s',
            PHP_BINARY,
            escapeshellarg(
                'call_user_func(unserialize(base64_decode(\'' . $serialized . '\')));'
            )
        );

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
