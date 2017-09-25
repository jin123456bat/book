<?php
namespace book\control;

use framework\core\request;
use framework\core\view;
use framework\vendor\csrf;
use framework\core\response\message;
use framework\core\http;

class user extends \book\extend\control
{
	function login()
	{
		if (request::method() == 'post')
		{
			$user = new \book\entity\user(request::post());
			if($user->login())
			{
				$user->saveSession();
				return new message('登录成功',http::url('index', 'index'));
			}
			else
			{
				return new message('用户名或密码错误');
			}
		}
		else
		{
			return new view('user/login.html');
		}
	}
	
	function register()
	{
		if (request::method() == 'post')
		{
			$user = new \book\entity\user(request::post());
			if ($user->validate())
			{
				$user->save();
				return new message('注册成功',http::url('index', 'index'));
			}
			else
			{
				return new message(current(current($user->getError())));
			}
		}
		else
		{
			return new view('user/register.html');
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \framework\core\control::__access()
	 */
	function __access()
	{
		return array(
			//一个开启csrf验证的例子
			array(
				csrf::verify(request::post('token'))?'allow':'deny',
				'actions' => array(
					'login',
					'register',
				),
				'message' => new message('请刷新重试'),
				'express' => request::method() == 'post',
			),
		);
	}
}