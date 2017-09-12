<?php
// 定义根目录
! defined('ROOT') & define('ROOT', __DIR__);

// 调试模式
define('DEBUG', false);

// 定义框架的目录
! defined('SYSTEM_ROOT') & define("SYSTEM_ROOT", '../framework');
// 定义APP的目录
! defined('APP_ROOT') & define("APP_ROOT", './');
// 定义app的名称 app的代码必须放在app名称对应的文件夹里面
! define("APP_NAME", "book");

// 载入框架
include SYSTEM_ROOT . '/framework.php';

$framework = new framework();
$app = $framework->createApplication(APP_NAME, APP_ROOT);
$app->run();
