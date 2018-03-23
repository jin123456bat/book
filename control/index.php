<?php
namespace book\control;

use framework\core\view;
use framework\core\request;
use framework\core\response\json;
use framework\vendor\paginate;
use book\entity\user;
use framework\core\response\url;
use framework\core\response\message;
use framework\core\http;
use framework\core\model;
use framework\core\control;

class index extends control
{

	/**
	 * 书籍列表
	 *
	 * @return \framework\core\view
	 */
	function list()
	{
		$start = request::post('start', 0, 'i');
		$length = request::post('length', 100, 'i');
		
		$book = $this->model('book')->where('isdelete=?', array(
			0
		));
		
		$page = new paginate($book);
		$page->limit($start, $length);
		
		if (request::method() == 'post')
		{
			return new json(1, request::post('draw', 1), $page->fetch());
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
		$book_id = request::get('id', 0, 'i');
		$start = request::post('start', 0, 'i');
		$length = request::post('length', 20, 'i');
		$book = $this->model('book')->where('id=?', array(
			$book_id
		))->find();
		if (! empty($book))
		{
			
			$model = $this->model('article')->where('book_id=? and completed=? and isdelete=?', array(
				$book_id,
				1,
				0
			))->order('createtime', 'desc')->order('id', 'desc')->select(array(
				'id',
				'title'
			));
			
			$page = new paginate($model);
			$page->limit($start, $length);
			$data = $page->fetch();
			
			if (request::isAjax() && request::method() == 'post')
			{
				return new json(1, 'ok', $data);
			}
			else
			{
				$view = new view('book/article.html');
				if (! empty($data))
				{
					$book['new'] = current($data);
				}
				$view->assign('book', $book);
				$view->assign('pagesize', $page->pagesize($length));
				$view->assign('article', $data);
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
		$id = request::get('id', 0, 'i');
		$article = $this->model('article')->where('id=?', array(
			$id
		))->find();
		
		$article['prev_id'] = $this->model('article')->where('id<? and book_id=? and completed=? and isdelete=?', array(
			$id,
			$article['book_id'],
			1,
			0
		))->order('createtime', 'desc')->order('id', 'desc')->scalar('id');
		$article['next_id'] = $this->model('article')->where('id>? and book_id=? and completed=? and isdelete=?', array(
			$id,
			$article['book_id'],
			1,
			0
		))->order('createtime', 'asc')->order('id', 'asc')->scalar('id');
		$article['content'] = trim($article['content']);
		
		$book = $this->model('book')->where('id=?', array(
			$article['book_id']
		))->find();
		
		$view = new view('book/content.html');
		$view->assign('article', $article);
		$view->assign('book', $book);
		
		$user = user::getUserBySession();
		if (! empty($user))
		{
			$this->model('history')->duplicate(array(
				'time' => date('Y-m-d H:i:s')
			))->insert(array(
				'bid' => $article['book_id'],
				'aid' => $id,
				'uid' => $user->id
			));
		}
		
		return $view;
	}

	function add_to_bookshelf()
	{
		$id = request::get('id');
		
		if (! empty($this->model('book')->where('id=?', array(
			$id
		))->find()))
		{
			if ($this->model('shelf')->insert(array(
				'uid' => user::getUserBySession()->id,
				'bid' => $id,
				'createtime' => date('Y-m-d H:i:s')
			)))
			{
				return new message('添加成功', http::url('index', 'article', array(
					'id' => $id
				)));
			}
			else
			{
				return new message('添加失败', http::url('index', 'article', array(
					'id' => $id
				)));
			}
		}
		return new message('参数错误', http::url('index', 'article', array(
			'id' => $id
		)));
	}

	/**
	 * 书架
	 *
	 * @return \framework\core\view
	 */
	function bookshelf()
	{
		$start = request::post('start', 0, 'i');
		$length = request::post('length', 100, 'i');
		
		$book = $this->model('book')->where('isdelete=?', array(
			0
		))->where('id in (select bid from shelf where uid=?)', array(
			user::getUserBySession()->id
		));
		
		$page = new paginate($book);
		$page->limit($start, $length);
		
		$data = $page->fetch();
		
		foreach ($data as &$d)
		{
			$new = $this->model('article')->where('book_id=? and completed=?', array(
				$d['id'],
				1
			))->order('createtime', 'desc')->order('id', 'desc')->limit(1)->find('id,title');
			$d['new'] = $new;
			
			$last = $this->model('history')->where('bid=? and uid=?', array(
				$d['id'],
				user::getUserBySession()->id
			))->leftJoin('article', 'article.id=history.aid')->order('time', 'desc')->limit(1)->find(array(
				'article.id',
				'article.title'
			));
			$d['last'] = $last;
		}
		
		if (request::method() == 'post')
		{
			
			return new json(1, request::post('draw', 1), $data);
		}
		else
		{
			$view = new view('book/shelf.html');
			$view->assign('book', $data);
			return $view;
		}
	}

	/**
	 * 首页
	 *
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
				'message' => new url('user', 'login'),
				'actions' => array(
					'bookshelf',
					'add_to_bookshelf'
				)
			)
		);
	}
}