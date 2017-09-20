<?php
namespace book\entity;
use framework\core\entity;
use framework\vendor\encryption;

class user extends entity
{
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
}