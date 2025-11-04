<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Filters;

use Horat1us\Yii\Schedule\Filters\Cron;
use Horat1us\Yii\Schedule\Exceptions\InvalidFilterException;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public function testValidCronExpression(): void
    {
        $filter = new Cron('* * * * *');
        $this->assertInstanceOf(Cron::class, $filter);
    }

    public function testInvalidCronExpressionThrowsException(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Invalid cron expression: invalid');

        new Cron('invalid');
    }

    public function testPassesWhenDue(): void
    {
        // Every minute
        $filter = new Cron('* * * * *');
        $dateTime = new \DateTime('2025-01-01 12:34:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassWhenNotDue(): void
    {
        // Every day at 1 AM
        $filter = new Cron('0 1 * * *');
        $dateTime = new \DateTime('2025-01-01 12:34:00'); // 12:34 PM

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testPassesAtSpecificTime(): void
    {
        // Every day at 1 AM
        $filter = new Cron('0 1 * * *');
        $dateTime = new \DateTime('2025-01-01 01:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testGetDescription(): void
    {
        $filter = new Cron('0 1 * * *');
        $this->assertSame('Cron: 0 1 * * *', $filter->getDescription());
    }

    public function testGetNextRunDate(): void
    {
        $filter = new Cron('0 1 * * *');
        $current = new \DateTime('2025-01-01 00:00:00');
        $next = $filter->getNextRunDate($current);

        $this->assertSame('2025-01-01 01:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function testGetPreviousRunDate(): void
    {
        $filter = new Cron('0 1 * * *');
        $current = new \DateTime('2025-01-02 00:00:00');
        $prev = $filter->getPreviousRunDate($current);

        $this->assertSame('2025-01-01 01:00:00', $prev->format('Y-m-d H:i:s'));
    }

    public function testEvery15Minutes(): void
    {
        $filter = new Cron('*/15 * * * *');

        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 12:00:00')));
        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 12:15:00')));
        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 12:30:00')));
        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 12:45:00')));
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 12:14:00')));
    }

    public function testWeekdaysOnly(): void
    {
        $filter = new Cron('0 9 * * 1-5'); // 9 AM Monday-Friday

        // Monday
        $this->assertTrue($filter->passes(new \DateTime('2025-01-06 09:00:00')));

        // Saturday
        $this->assertFalse($filter->passes(new \DateTime('2025-01-04 09:00:00')));
    }
}
