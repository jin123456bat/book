<?php
return array(
	'session' => array(
		'use_cookies' => 0, // session的传递通过cookie实现
		'use_only_cookies' => 0, // 只使用cookie中的session_id
		'use_trans_sid' => 1, // 禁止url中的session_id
	),
);
