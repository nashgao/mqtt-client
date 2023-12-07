<?php

declare(strict_types=1);

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Context\ApplicationContext;

error_reporting(E_ALL);
date_default_timezone_set('Australia/Brisbane');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

Swoole\Runtime::enableCoroutine(true);

require BASE_PATH . '/vendor/autoload.php';

Hyperf\Di\ClassLoader::init();

/** @var Psr\Container\ContainerInterface $container */
$container = new Container((new DefinitionSourceFactory())());
/** @var Container $container */
$container = ApplicationContext::setContainer($container);

$container->get(Hyperf\Contract\ApplicationInterface::class);
