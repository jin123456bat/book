<?php
use framework\core\env;

if (env::php_sapi_name() == 'cli' || $_SERVER['HTTP_HOST'] == 'book.techer.top')
{
	return array(
		'type' => 'mysql',
		'server' => 'localhost',
		'dbname' => 'book',
		'user' => 'root',
		'password' => 'jin2164389',
		'charset' => 'utf8'
	);
}
else
{
	return array(
		'type' => 'mysql',
		'server' => 'localhost',
		'dbname' => 'test',
		'user' => 'root',
		'password' => '',
		'charset' => 'utf8'
	);
}