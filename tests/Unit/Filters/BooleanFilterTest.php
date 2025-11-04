<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Filters;

use Horat1us\Yii\Schedule\Filters\BooleanFilter;
use PHPUnit\Framework\TestCase;

class BooleanFilterTest extends TestCase
{
    public function testPassesWhenTrue(): void
    {
        $filter = new BooleanFilter(true);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassWhenFalse(): void
    {
        $filter = new BooleanFilter(false);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testGetDescriptionForTrue(): void
    {
        $filter = new BooleanFilter(true);
        $this->assertSame('Enabled', $filter->getDescription());
    }

    public function testGetDescriptionForFalse(): void
    {
        $filter = new BooleanFilter(false);
        $this->assertSame('Disabled', $filter->getDescription());
    }

    public function testGetDescriptionWithCustomDescription(): void
    {
        $filter = new BooleanFilter(true, 'Custom description');
        $this->assertSame('Custom description', $filter->getDescription());

        $filter = new BooleanFilter(false, 'Another description');
        $this->assertSame('Another description', $filter->getDescription());
    }

    public function testWithConstant(): void
    {
        // Simulating YII_ENV_PROD constant
        $isProduction = true;
        $filter = new BooleanFilter($isProduction, 'Production environment');

        $this->assertTrue($filter->passes(new \DateTime()));
        $this->assertSame('Production environment', $filter->getDescription());
    }

    public function testAlwaysReturnsTheSameValue(): void
    {
        $trueFilter = new BooleanFilter(true);
        $falseFilter = new BooleanFilter(false);

        // Test multiple times to ensure consistency
        for ($i = 0; $i < 5; $i++) {
            $dt = new \DateTime("+{$i} hours");
            $this->assertTrue($trueFilter->passes($dt));
            $this->assertFalse($falseFilter->passes($dt));
        }
    }
}
