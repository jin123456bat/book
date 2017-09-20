<?php
if ($_SERVER['HTTP_HOST'] == 'localhost')
{
	define('ROOT', '..');
}
else
{
	define('ROOT','/var/www');
}
// 调试模式
define('DEBUG', false);

// 定义框架的目录
! defined('SYSTEM_ROOT') & define("SYSTEM_ROOT", ROOT.'/framework');

// 定义APP的目录
! defined('APP_ROOT') & define("APP_ROOT", ROOT.'/book');
// 定义app的名称 app的代码必须放在app名称对应的文件夹里面
! define("APP_NAME", "book");

// 载入框架
include SYSTEM_ROOT . '/framework.php';

$framework = new framework();
$app = $framework->createApplication(APP_NAME, APP_ROOT);
$app->run();
