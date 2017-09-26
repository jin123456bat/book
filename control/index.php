<?php
namespace book\control;

use framework\core\view;
use framework\core\request;
use framework\core\response\json;
use framework\vendor\paginate;
use book\extend\control;
use book\entity\user;
use framework\core\response\url;

class index extends control
{
	/**
	 * 书籍列表
	 * @return \framework\core\view
	 */
	function list()
	{
		$start = request::post('start',0,NULL,'i');
		$length = request::post('length',100,NULL,'i');
		
		$book = $this->model('book')
		->where('isdelete=?',array(
			0
		));
		
		$page = new paginate($book);
		$page->limit($start,$length);
		
		if (request::method() == 'post')
		{
			return new json(1,request::post('draw',1),$page->fetch());
		}
		else
		{
			$view = new view('book/list.html');
			$view->assign('book', $page->fetch());
			return $view;
		}
	}
	
	/**
	 * 书籍的文章列表
	 */
	function article()
	{
		$book_id = request::get('id',0,NULL,'i');
		$start = request::post('start',0,NULL,'i');
		$length = request::post('length',20,NULL,'i');
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
	
	/**
	 * 书架
	 * @return \framework\core\view
	 */
	function bookshelf()
	{
		
		$start = request::post('start',0,NULL,'i');
		$length = request::post('length',100,NULL,'i');
		
		$book = $this->model('book')
		->where('isdelete=?',array(
			0
		))->where('id in (select bid from shelf where uid=?)',array(
			user::getUserBySession()->id,
		));
		
		$page = new paginate($book);
		$page->limit($start,$length);
		if (request::method() == 'post')
		{
			$data = $page->fetch();
			foreach ($data as &$d)
			{
				$new = $this->model('article')->where('book_id=? and completed=?',array(
					$d['id'],1
				))->order('createtime','desc')->order('id','desc')->limit(1)->find('id,title');
				$d['new'] = $new;
			}
			return new json(1,request::post('draw',1),$data);
		}
		else
		{
			$view = new view('book/shelf.html');
			$view->assign('book', $page->fetch());
			return $view;
		}
	}
	
	/**
	 * 首页
	 * @return \framework\core\view
	 */
	function index()
	{
		return $this->list();
	}
	
	function __access()
	{
		return array(
			array(
				'deny',
				'express' => empty(user::getUserBySession()),
				'message' => new url('user','login'),
				'actions' => array(
					'bookshelf',
				)
			)
		);
	}
}