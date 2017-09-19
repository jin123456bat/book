<?php
namespace book\control;
use framework\core\control;
use framework\core\view;

class admin extends control
{
	function index()
	{
		$view = new view('admin/index.html');
		return $view;
	}
	
	/**
	 * 登录页
	 */
	function login()
	{
		
	}
}