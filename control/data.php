<?php
namespace book\control;

use framework\core\control;
use framework\core\http;
use book\entity\book;
use framework\core\request;
use framework\core\response\url;
use framework\core\response\message;

class data extends control
{

	/**
	 * 添加书籍
	 * 传递url过来
	 */
	function create()
	{
		$url = request::post('url');
		$url = parse_url($url);
		if (empty($url))
		{
			return new message('url解析失败', http::url('admin', 'index'));
		}
		
		$query = isset($url['query']) && ! empty($url['query']) ? $url['query'] : '';
		$path = isset($url['path']) && ! empty($url['path']) ? $url['path'] : '';
		$scheme = isset($url['scheme']) && ! empty($url['scheme']) ? $url['scheme'] : '';
		$host = isset($url['host']) && ! empty($url['host']) ? $url['host'] : '';
		
		// 目录url
		$url = $scheme . '://' . $host . $path . $query;
		
		$data = array(
			'url' => $url,
			'source' => $scheme . '://' . $host
		);
		
		$classname = str_replace('.', '_', $host);
		$namespace = '\\book\\entity\\' . $classname;
		if (! class_exists($namespace, true))
		{
			return new message('没有找到对应的解析方式');
		}
		$book = new $namespace($data);
		
		// 更新基础信息
		$book->name = $book->getTitle();
		$book->author = $book->getAuthor();
		$book->description = $book->getDescription();
		$book->completed = $book->getIsCompleted() ? 1 : 0;
		$book->isdelete = 0;
		$book->download_completed = 0;
		$book->image = $book->getImage();
		if ($book->validate())
		{
			if ($book->save())
			{
				$list = $book->getArticleList();
				
				$this->model('article')->startCompress();
				foreach ($list as $article)
				{
					$this->model('article')->insert(array(
						'book_id' => $book->id,
						'content' => '',
						'title' => $article['name'],
						'url' => $article['url'],
						'completed' => 0
					));
				}
				$this->model('article')->commitCompress();
				return new \framework\core\response\message('添加成功', \framework\core\http::url('admin', 'index'));
			}
			else
			{
				return new \framework\core\response\message('添加失败', \framework\core\http::url('admin', 'index'));
			}
		}
		else
		{
			$error = $book->getError();
			return new \framework\core\response\message($error['name'][0], \framework\core\http::url('admin', 'index'));
		}
	}

	/**
	 * 更新最新的文章列表
	 * 对于未完结的文章来说，只更新最新的文章列表
	 */
	function complete()
	{
	}

	/**
	 *
	 * @return string[]
	 */
	function __single()
	{
		return array(
			'download',
			'complete'
		);
	}

	/**
	 * 切换数据源
	 */
	function change()
	{
		$books = $this->model('book')
			->where(array(
			'completed' => 0,
			'isdelete' => 0
		))
			->select();
		$i = 0;
		foreach ($books as $book)
		{
			$url = 'http://zhannei.baidu.com/cse/search?q=%s&click=1&s=5199337987683747968&nsid=';
			$url = sprintf($url, $book['name']);
			
			$content = http::get($url);
			
			if (preg_match('/<a cpos="title" href="(?<url>[^"]*)"/', $content, $match))
			{
				if ($this->model('book')
					->where(array(
					'id' => $book['id']
				))
					->limit(1)
					->update(array(
					'url' => $match['url']
				)))
				{
					$i ++;
				}
			}
		}
		echo $i . '完成';
	}

	/**
	 * 文章下载
	 */
	function download()
	{
		// 同步目录
		$books = $this->model('book')
			->where('completed=? and isdelete=?', array(
			0,
			0
		))
			->select();
		// 		$books = $this->model('book')
		// 			->where('id=?', [
		// 			27
		// 		])
		// 			->select();
		foreach ($books as $book)
		{
			$url = $book['url'];
			$url = parse_url($url);
			
			$classname = str_replace('.', '_', $url['host']);
			$namespace = '\\book\\entity\\' . $classname;
			if (! class_exists($namespace, true))
			{
				echo "无对应的解析方式";
				continue;
			}
			
			$book = new $namespace($book);
			$list = $book->getNewArticle();
			if (! empty($list))
			{
				$this->model('article')->startCompress();
				foreach ($list as $article)
				{
					$this->model('article')->insert(array(
						'book_id' => $book->id,
						'content' => '',
						'title' => $article['name'],
						'url' => $article['url'],
						'completed' => 0
					));
				}
				$this->model('article')->commitCompress();
			}
			
			$book->completed = $book->getIsCompleted() ? 1 : 0;
			if (empty($book->image))
			{
				$book->image = $book->getImage();
			}
			$book->save();
		}
		
		// 同步文章内容
		$result = $this->model('article')
			->where('completed=? and isdelete=?', array(
			0,
			0
		))
			->select();
		// 		$result = $this->model('article')
		// 			->where('id=?', array(
		// 			20841
		// 		))
		// 			->select();
		foreach ($result as $r)
		{
			$url = parse_url($r['url']);
			
			$classname = str_replace('.', '_', $url['host']);
			$namespace = '\\book\\entity\\' . $classname;
			if (! class_exists($namespace, true))
			{
				echo "无对应的解析方式";
				continue;
			}
			
			$content = call_user_func($namespace . '::getArticleContent', $r['url']);
			
			if (! empty($content))
			{
				if ($this->model('article')
					->where('id=?', array(
					$r['id']
				))
					->limit(1)
					->update(array(
					'content' => $content,
					'completed' => 1,
					'completed_time' => date('Y-m-d H:i:s')
				)))
				{
					echo "下载完成:《" . $r['title'] . "》";
				}
				else
				{
					echo "更新失败";
				}
			}
		}
	}
}
?>