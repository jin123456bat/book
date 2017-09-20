<?php
namespace book\control;
use framework\core\control;
use framework\core\request;
use framework\core\view;

class user extends control
{
	function login()
	{
		if (request::method() == 'post')
		{
			
		}
		else
		{
			return new view('user/login.html');
		}
	}
}