<?php
if ($_SERVER['HTTP_HOST'] == 'book.techer.top')
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