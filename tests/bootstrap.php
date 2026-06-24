<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (! defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';
