<?php

declare(strict_types=1);

namespace Horat1us\Yii\Schedule\Console;

use yii\console\Controller as BaseController;

/**
 * Schedule management console controller.
 *
 * Usage:
 *   php yii schedule/run     - Run all due tasks
 *   php yii schedule/list    - List all scheduled tasks
 *   php yii schedule/run --currentTime="2025-01-01 12:00:00"  - Test with specific time
 *   php yii schedule/list -t "2025-01-01 12:00:00"            - List tasks for specific time
 *
 * This controller is a thin wrapper around action classes.
 * All business logic is contained in RunAction and ListAction.
 */
class Controller extends BaseController
{
    /**
     * @var string The default action of this controller
     */
    public $defaultAction = 'list';

    /**
     * Current time for scheduling (defaults to "now").
     * Useful for testing scheduled tasks on production or running missed schedules.
     *
     * @var string
     */
    public $currentTime = 'now';

    /**
     * Declare inline actions.
     *
     * @return array
     */
    public function actions(): array
    {
        return [
            'run' => RunAction::class,
            'list' => ListAction::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['currentTime']);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), ['t' => 'currentTime']);
    }
}
