<?php
namespace book\entity;
use framework\core\entity;
use framework\vendor\encryption;
use framework\core\session;

class user extends entity
{
	static function getUserBySession()
	{
		$user = session::get('user');
		if (!empty($user)) {
			return new self(array_shift($user));
		}
		return null;
	}
	
	private function encrypt($data,$password)
	{
		return openssl_encrypt($data, 'aes-256-cbc-hmac-sha256', $password);
	}
	
	function __preInsert()
	{
		if(isset($this->_data['password']) && !empty($this->_data['password']))
		{
			$this->_data['salt'] = encryption::random(32);
			$this->_data['password'] = $this->encrypt($this->_data['password'], $this->_data['salt']);
		}
	}
	
	function __preUpdate()
	{
		if(isset($this->_data['password']) && !empty($this->_data['password']))
		{
			$this->_data['salt'] = encryption::random(32);
			$this->_data['password'] = $this->encrypt($this->_data['password'], $this->_data['salt']);
		}
	}
	
	function login()
	{
		if (isset($this->_data['name']) && isset($this->_data['password']))
		{
			$old_password = $this->_data['password'];
			$this->_data = $this->findByFiled('name', $this->_data['name']);
			if (!empty($this->_data))
			{
				if($this->_data['password'] == $this->encrypt($old_password, $this->_data['salt']))
				{
					return true;
				}
			}
		}
		return false;
	}
	
	function saveSession()
	{
		$user = session::get('user');
		if (empty($user))
		{
			$user = array();
		}
		$has = false;
		foreach ($user as $u)
		{
			if ($u['id'] == $this->_data['id'])
			{
				$has = true;
				break;
			}
		}
		if (!$has)
		{
			$user[] = array(
				'id' => $this->_data['id'],
				'name' => $this->_data['name'],
			);
			session::set('user', $user);
		}
	}
}