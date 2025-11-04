<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Filters;

use Cron\CronExpression;
use Horat1us\Yii\Schedule\Contracts\FilterInterface;
use Horat1us\Yii\Schedule\Exceptions\InvalidFilterException;

/**
 * Filter that checks if a task should run based on a cron expression.
 *
 * Example: new Cron('* * * * *') runs every minute
 *          new Cron('0 1 * * *') runs daily at 1:00 AM
 */
readonly class Cron implements FilterInterface
{
    private CronExpression $expression;

    public function __construct(string $expression)
    {
        try {
            $this->expression = new CronExpression($expression);
        } catch (\Throwable $e) {
            throw InvalidFilterException::invalidCronExpression($expression, $e);
        }
    }

    public function passes(\DateTimeInterface $dateTime): bool
    {
        return $this->expression->isDue($dateTime);
    }

    public function getDescription(): string
    {
        return "Cron: {$this->expression->getExpression()}";
    }

    public function getNextRunDate(\DateTimeInterface $currentTime = new \DateTime()): \DateTimeInterface
    {
        return $this->expression->getNextRunDate($currentTime);
    }

    public function getPreviousRunDate(\DateTimeInterface $currentTime = new \DateTime()): \DateTimeInterface
    {
        return $this->expression->getPreviousRunDate($currentTime);
    }
}
