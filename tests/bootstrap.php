<?php

declare(strict_types=1);

// Ensure we have the constants defined
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_ENV_TEST') or define('YII_ENV_TEST', true);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', false);
defined('YII_ENV_PROD') or define('YII_ENV_PROD', false);

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Yii autoloader
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Create a minimal mock console application for testing
$config = [
    'id' => 'test-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'components' => [
        'log' => [
            'traceLevel' => 0,
            'targets' => [],
        ],
    ],
];

new yii\console\Application($config);
