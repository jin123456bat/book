<?php
namespace book\control;

use framework\core\webControl;
use framework\core\view;

class index extends webControl
{
	/**
	 * 书籍列表
	 * @return \framework\core\view
	 */
	function list()
	{
		$book = $this->model('book')->where('isdelete=?',array(
			0
		))->select();
		$view = new view('book/list.html');
		$view->assign('book', $book);
		return $view;
	}
	
	/**
	 * 书籍的文章列表
	 */
	function article()
	{
		
	}
	
	/**
	 * 文章内容
	 */
	function content()
	{
		
	}
	
	function index()
	{
		return $this->list();
	}
	
	function login()
	{
		
	}
}