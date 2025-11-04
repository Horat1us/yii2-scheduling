<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Commands;

use Horat1us\Yii\Schedule\Commands\ShellCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ShellCommandTest extends TestCase
{
    public function testBasicCommand(): void
    {
        $command = new ShellCommand('echo "Hello World"');
        $process = $command->toProcess();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertSame('echo "Hello World"', $process->getCommandLine());
    }

    public function testComplexCommand(): void
    {
        $cmd = 'curl -X POST https://example.com/webhook -d "data=test"';
        $command = new ShellCommand($cmd);
        $process = $command->toProcess();

        $this->assertSame($cmd, $process->getCommandLine());
    }

    public function testGetDescriptionWithoutCustomDescription(): void
    {
        $cmd = 'echo "test"';
        $command = new ShellCommand($cmd);

        $this->assertSame($cmd, $command->getDescription());
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $command = new ShellCommand('echo "test"', 'Test command');

        $this->assertSame('Test command', $command->getDescription());
    }

    public function testPipeCommands(): void
    {
        $command = new ShellCommand('cat /var/log/app.log | grep ERROR');
        $process = $command->toProcess();

        $this->assertStringContainsString('cat /var/log/app.log', $process->getCommandLine());
        $this->assertStringContainsString('grep ERROR', $process->getCommandLine());
    }

    public function testCommandWithRedirection(): void
    {
        $command = new ShellCommand('echo "test" > /tmp/output.txt');
        $process = $command->toProcess();

        $this->assertStringContainsString('>', $process->getCommandLine());
        $this->assertStringContainsString('/tmp/output.txt', $process->getCommandLine());
    }

    public function testCommandPreservesExactString(): void
    {
        $complexCmd = 'cd /path && php artisan queue:work --tries=3';
        $command = new ShellCommand($complexCmd);
        $process = $command->toProcess();

        $this->assertSame($complexCmd, $process->getCommandLine());
    }
}
