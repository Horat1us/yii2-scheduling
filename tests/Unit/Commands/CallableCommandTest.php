<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Commands;

use Horat1us\Yii\Schedule\Commands\CallableCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CallableCommandTest extends TestCase
{
    public function testArrayCallableCommand(): void
    {
        $command = new CallableCommand([self::class, 'dummyMethod']);
        $process = $command->toProcess();

        $this->assertInstanceOf(Process::class, $process);
    }

    public function testStringCallableCommand(): void
    {
        $command = new CallableCommand('phpinfo');
        $process = $command->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString('phpinfo', $process->getCommandLine());
    }

    public function testClosureThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CallableCommand only supports array callables and function names, not closures');

        $command = new CallableCommand(fn() => true);
        $command->toProcess();
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $command = new CallableCommand([self::class, 'dummyMethod'], 'Custom description');

        $this->assertSame('Custom description', $command->getDescription());
    }

    public function testGetDescriptionForArrayCallable(): void
    {
        $command = new CallableCommand([self::class, 'dummyMethod']);
        $description = $command->getDescription();

        $this->assertStringContainsString(self::class, $description);
        $this->assertStringContainsString('dummyMethod', $description);
    }

    public function testProcessContainsCallUserFunc(): void
    {
        $command = new CallableCommand([self::class, 'dummyMethod']);
        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        $this->assertStringContainsString('call_user_func', $commandLine);
    }

    public function testProcessUsesPhpBinary(): void
    {
        $command = new CallableCommand([self::class, 'dummyMethod']);
        $process = $command->toProcess();

        $this->assertStringContainsString(PHP_BINARY, $process->getCommandLine());
    }

    public static function dummyMethod(): void
    {
        // Dummy method for testing
    }
}
