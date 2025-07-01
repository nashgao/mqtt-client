<?php

declare(strict_types=1);

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Psr\Container\ContainerInterface;
use Swoole\Runtime;

error_reporting(E_ALL);
date_default_timezone_set('Australia/Brisbane');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

if (extension_loaded('swoole')) {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

    Runtime::enableCoroutine(true);
}

require BASE_PATH . '/vendor/autoload.php';

ClassLoader::init();

/** @var ContainerInterface $container */
$container = new Container((new DefinitionSourceFactory())());
/** @var Container $container */
$container = ApplicationContext::setContainer($container);

$container->get(ApplicationInterface::class);
