<?php
namespace book\extend;

use book\entity\user;
use framework\core\view;

class book extends \framework\core\application
{

	function onRequestEnd($control, $action, $response = null)
	{
		if ($response instanceof view)
		{
			$user = user::getUserBySession();
			if (! empty($user))
			{
				$response->assign('user', array(
					'name' => $user->name
				));
				return $response;
			}
		}
	}
}