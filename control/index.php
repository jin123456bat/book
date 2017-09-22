<?php
namespace book\control;

use framework\core\webControl;
use framework\core\view;
use framework\core\request;
use framework\core\response\json;
use framework\vendor\paginate;
use framework\core\response\url;

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
		$book_id = request::get('id',0,NULL,'i');
		$start = request::post('start',0,NULL,'i');
		$length = 20;
		$book = $this->model('book')->where('id=?',array($book_id))->find();
		if (!empty($book))
		{
			$model = $this->model('article')
			->where('book_id=? and completed=?',array($book_id,1))
			->order('createtime','desc')
			->order('id','desc')
			->select(array(
				'id',
				'title',
			));
			
			$page = new paginate($model);
			$page->limit($start, $length);
			if (request::isAjax() && request::method()=='post')
			{
				return new json(1,'ok',$page->fetch());
			}
			else
			{
				$view = new view('book/article.html');
				$view->assign('book', $book);
				$view->assign('pagesize', $page->pagesize($length));
				$view->assign('article', $page->fetch());
				return $view;
			}
		}
		return '书籍不存在';
	}
	
	/**
	 * 文章内容
	 */
	function content()
	{
		$id = request::get('id',0,null,'i');
		$article = $this->model('article')->where('id=?',array($id))->find();
		
		$article['prev_id'] = $this->model('article')->where('id<? and book_id=? and completed=? and isdelete=?',array($id,$article['book_id'],1,0))->order('createtime','desc')->order('id','desc')->scalar('id');
		$article['next_id'] = $this->model('article')->where('id>? and book_id=? and completed=? and isdelete=?',array($id,$article['book_id'],1,0))->order('createtime','asc')->order('id','asc')->scalar('id');
		$article['content'] = trim($article['content']);
		
		$book = $this->model('book')->where('id=?',array($article['book_id']))->find();
		
		$view = new view('book/content.html');
		$view->assign('article', $article);
		$view->assign('book', $book);
		
		return $view;
	}
	
	function index()
	{
		return $this->list();
	}
	
	function login()
	{
		
	}
}