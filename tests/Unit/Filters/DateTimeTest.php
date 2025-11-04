<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Filters;

use Horat1us\Yii\Schedule\Filters\DateTime;
use Horat1us\Yii\Schedule\Exceptions\InvalidFilterException;
use PHPUnit\Framework\TestCase;

class DateTimeTest extends TestCase
{
    public function testValidDateTime(): void
    {
        $filter = new DateTime('2025-01-01 12:00');
        $this->assertInstanceOf(DateTime::class, $filter);
    }

    public function testInvalidDateTimeThrowsException(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('Invalid datetime: not-a-date');

        new DateTime('not-a-date');
    }

    public function testPassesAtExactTime(): void
    {
        $filter = new DateTime('2025-01-01 12:00:00');
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testPassesIgnoresSeconds(): void
    {
        $filter = new DateTime('2025-01-01 12:00');
        $dateTime = new \DateTime('2025-01-01 12:00:45'); // 45 seconds past

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassBeforeTime(): void
    {
        $filter = new DateTime('2025-01-01 12:00');
        $dateTime = new \DateTime('2025-01-01 11:59:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testDoesNotPassAfterTime(): void
    {
        $filter = new DateTime('2025-01-01 12:00');
        $dateTime = new \DateTime('2025-01-01 12:01:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testGetDescription(): void
    {
        $filter = new DateTime('2025-01-01 12:00');
        $this->assertSame('DateTime: 2025-01-01 12:00:00', $filter->getDescription());
    }

    public function testGetTargetDateTime(): void
    {
        $filter = new DateTime('2025-01-01 12:34:56');
        $target = $filter->getTargetDateTime();

        // Should be rounded to start of minute
        $this->assertSame('2025-01-01 12:34:00', $target->toDateTimeString());
    }

    public function testVariousDateFormats(): void
    {
        $formats = [
            '2025-01-01 12:00',
            '2025-01-01 12:00:00',
            '2025/01/01 12:00',
            'January 1, 2025 12:00 PM',
            'now',
            'tomorrow 12:00',
        ];

        foreach ($formats as $format) {
            $filter = new DateTime($format);
            $this->assertInstanceOf(DateTime::class, $filter, "Failed to parse: {$format}");
        }
    }
}
