<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Tests\Unit\Filters;

use Horat1us\Yii\Schedule\Filters\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testPassesInMatchingEnvironment(): void
    {
        // YII_ENV is set to 'test' in phpunit.xml
        $filter = new Environment('test');
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassInDifferentEnvironment(): void
    {
        // YII_ENV is 'test', not 'prod'
        $filter = new Environment('prod');
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testPassesWithMultipleEnvironments(): void
    {
        // YII_ENV is 'test', which is in the array
        $filter = new Environment(['prod', 'test', 'staging']);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertTrue($filter->passes($dateTime));
    }

    public function testDoesNotPassWithMultipleEnvironmentsWhenNotMatched(): void
    {
        // YII_ENV is 'test', not in this array
        $filter = new Environment(['prod', 'staging']);
        $dateTime = new \DateTime('2025-01-01 12:00:00');

        $this->assertFalse($filter->passes($dateTime));
    }

    public function testGetDescriptionForSingleEnvironment(): void
    {
        $filter = new Environment('prod');
        $this->assertSame('Environment: prod', $filter->getDescription());
    }

    public function testGetDescriptionForMultipleEnvironments(): void
    {
        $filter = new Environment(['prod', 'staging']);
        $this->assertSame('Environment: prod, staging', $filter->getDescription());
    }

    public function testEnvironmentCheckIsCaseSensitive(): void
    {
        // YII_ENV is 'test' (lowercase)
        $filter = new Environment('TEST'); // uppercase

        $this->assertFalse($filter->passes(new \DateTime()));
    }

    public function testAcceptsStringOrArray(): void
    {
        $stringFilter = new Environment('test');
        $arrayFilter = new Environment(['test']);

        $dt = new \DateTime();
        $this->assertTrue($stringFilter->passes($dt));
        $this->assertTrue($arrayFilter->passes($dt));
    }
}
