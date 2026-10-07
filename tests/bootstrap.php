<?php

$testingViewsPath = sys_get_temp_dir() . '/km314-testing-views-' . getmypid();

if (!is_dir($testingViewsPath)) {
    mkdir($testingViewsPath, 0777, true);
}

$testingEnvironment = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => 'km314-db',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'km314_testing',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'VIEW_COMPILED_PATH' => $testingViewsPath,
];

foreach ($testingEnvironment as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require dirname(__DIR__) . '/vendor/autoload.php';
