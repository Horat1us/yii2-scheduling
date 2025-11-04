<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Filters;

use Horat1us\Yii\Schedule\Filters\ClosureFilter;
use PHPUnit\Framework\TestCase;

class ClosureFilterTest extends TestCase
{
    public function testPassesWhenClosureReturnsTrue(): void
    {
        $filter = new ClosureFilter(fn(\DateTimeInterface $dt) => true);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassWhenClosureReturnsFalse(): void
    {
        $filter = new ClosureFilter(fn(\DateTimeInterface $dt) => false);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testClosureReceivesDateTime(): void
    {
        $receivedDateTime = null;
        $filter = new ClosureFilter(function (\DateTimeInterface $dt) use (&$receivedDateTime) {
            $receivedDateTime = $dt;
            return true;
        });

        $dateTime = new \DateTime('2025-01-01 12:00:00');
        $filter->passes($dateTime);

        $this->assertSame($dateTime, $receivedDateTime);
    }

    public function testCustomLogicInClosure(): void
    {
        // Only pass during 1 AM hour
        $filter = new ClosureFilter(fn(\DateTimeInterface $dt) => $dt->format('H') === '01');

        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 01:00:00')));
        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 01:59:00')));
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 02:00:00')));
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 00:59:00')));
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $filter = new ClosureFilter(
            fn(\DateTimeInterface $dt) => true,
            'Custom description'
        );

        $this->assertSame('Custom description', $filter->getDescription());
    }

    public function testGetDescriptionWithoutCustomDescription(): void
    {
        $filter = new ClosureFilter(fn(\DateTimeInterface $dt) => true);

        $this->assertSame('Custom closure filter', $filter->getDescription());
    }

    public function testClosureCanAccessExternalVariables(): void
    {
        $allowedHours = [9, 10, 11, 12, 13, 14, 15, 16, 17];
        $filter = new ClosureFilter(
            fn(\DateTimeInterface $dt) => in_array((int)$dt->format('H'), $allowedHours, true),
            'Business hours (9 AM - 5 PM)'
        );

        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 09:00:00')));
        $this->assertTrue($filter->passes(new \DateTime('2025-01-01 17:00:00')));
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 08:00:00')));
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 18:00:00')));
    }

    public function testClosureWithComplexLogic(): void
    {
        // First Monday of the month
        $filter = new ClosureFilter(
            fn(\DateTimeInterface $dt) =>
                $dt->format('N') === '1' && // Monday
                (int)$dt->format('d') <= 7,   // First week
            'First Monday of the month'
        );

        // January 6, 2025 is the first Monday
        $this->assertTrue($filter->passes(new \DateTime('2025-01-06 00:00:00')));

        // January 13, 2025 is the second Monday
        $this->assertFalse($filter->passes(new \DateTime('2025-01-13 00:00:00')));

        // January 1, 2025 is Wednesday
        $this->assertFalse($filter->passes(new \DateTime('2025-01-01 00:00:00')));
    }
}
