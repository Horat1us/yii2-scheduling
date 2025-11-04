<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Commands;

use Horat1us\Yii\Schedule\Commands\YiiConsoleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class YiiConsoleCommandTest extends TestCase
{
    public function testBasicCommand(): void
    {
        $command = new YiiConsoleCommand('migrate/up');
        $process = $command->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertStringContainsString('migrate/up', $process->getCommandLine());
    }

    public function testCommandWithOptions(): void
    {
        $command = new YiiConsoleCommand('migrate/up', [
            '--interactive' => 0,
            '--compact' => true,
        ]);

        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        $this->assertStringContainsString('--interactive=0', $commandLine);
        $this->assertStringContainsString('--compact', $commandLine);
    }

    public function testCommandWithFalseOptionIsExcluded(): void
    {
        $command = new YiiConsoleCommand('migrate/up', [
            '--interactive' => false,
        ]);

        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        $this->assertStringNotContainsString('--interactive', $commandLine);
    }

    public function testCommandWithNullOptionIsExcluded(): void
    {
        $command = new YiiConsoleCommand('migrate/up', [
            '--interactive' => null,
        ]);

        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        $this->assertStringNotContainsString('--interactive', $commandLine);
    }

    public function testCommandWithPositionalArguments(): void
    {
        $command = new YiiConsoleCommand('controller/action', [
            'arg1',
            'arg2',
        ]);

        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        $this->assertStringContainsString('arg1', $commandLine);
        $this->assertStringContainsString('arg2', $commandLine);
    }

    public function testCommandWithCustomYiiExecutable(): void
    {
        $command = new YiiConsoleCommand('migrate/up', [], '/custom/path/yii');
        $process = $command->toProcess();

        $this->assertStringContainsString('/custom/path/yii', $process->getCommandLine());
    }

    public function testCommandWithDefaultYiiExecutable(): void
    {
        $command = new YiiConsoleCommand('migrate/up');
        $process = $command->toProcess();

        $this->assertStringContainsString('./yii', $process->getCommandLine());
    }

    public function testGetDescription(): void
    {
        $command = new YiiConsoleCommand('migrate/up');
        $this->assertSame('yii migrate/up', $command->getDescription());
    }

    public function testGetDescriptionWithOptions(): void
    {
        $command = new YiiConsoleCommand('migrate/up', [
            '--interactive' => 0,
            '--compact' => true,
        ]);

        $description = $command->getDescription();
        $this->assertStringContainsString('migrate/up', $description);
        $this->assertStringContainsString('--interactive=0', $description);
        $this->assertStringContainsString('--compact', $description);
    }

    public function testArgumentsWithSpacesAreEscaped(): void
    {
        $command = new YiiConsoleCommand('test/action', [
            '--message' => 'hello world',
        ]);

        $process = $command->toProcess();
        $commandLine = $process->getCommandLine();

        // Should be quoted
        $this->assertMatchesRegularExpression('/--message=["\']hello world["\']/', $commandLine);
    }

    public function testCommandIncludesPhpBinary(): void
    {
        $command = new YiiConsoleCommand('migrate/up');
        $process = $command->toProcess();

        $this->assertStringContainsString(PHP_BINARY, $process->getCommandLine());
    }
}
