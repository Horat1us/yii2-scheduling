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
}
