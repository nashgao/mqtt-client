<?php

declare(strict_types=1);

use Swoole\Runtime;

error_reporting(E_ALL);
date_default_timezone_set('Australia/Brisbane');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

if (extension_loaded('swoole')) {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

    Runtime::enableCoroutine(1);
}

require BASE_PATH . '/vendor/autoload.php';
