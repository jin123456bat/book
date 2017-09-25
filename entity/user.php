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
	
	/**
	 * 密码加密函数
	 * @param unknown $data 密码明文
	 * @param unknown $password 盐值  长度必须是64位  范围0-9a-zA-Z
	 * @return string
	 */
	private function encrypt($data,$password)
	{
		$result = crypt($data,'$2a$05$'.$password.'$');
		return $result;
	}
	
	function __rules()
	{
		return array(
			'required' => array(
				'name' => array(
					'message' => '请填写用户名',
				),
				'password' => array(
					'message' => '请填写密码',
				),
			),
			'eq' => array(
				'password' => array(
					'data' => '@repassword',
					'message' => '两次输入的密码不一致',
				)
			),
			'unique'=>array(
				'name' => array(
					'message' => '用户名已经注册',
				)
			)
		);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \framework\core\entity::__preInsert()
	 */
	function __preInsert()
	{
		if(isset($this->_data['password']) && !empty($this->_data['password']))
		{
			$this->_data['salt'] = encryption::random(64);
			$this->_data['password'] = $this->encrypt($this->_data['password'], $this->_data['salt']);
		}
	}
	
	function __preUpdate()
	{
		if(isset($this->_data['password']) && !empty($this->_data['password']))
		{
			$this->_data['salt'] = encryption::random(64);
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