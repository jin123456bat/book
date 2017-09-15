<?php 
namespace book\control;
use framework\core\Control;
use framework\core\database\mysql\field;
use framework\core\http;
use framework\vendor\image;
use book\entity\book;
use framework\core\request;

class data extends Control
{
	/**
	 * 添加书籍
	 * 传递url过来
	 */
	function create()
	{
		$url = request::post('url');
		$url = parse_url($url);
		
		//主机host
		$host = $url['scheme'].'://'.$url['host'];
		//目录url
		$url = $host.$url['path'].$url['query'];
		
		$data = array(
			'url' => $url,
			'source' => $host,
		);
		$book = new book($data);
		$list = $book->getArticleList();
		
		$this->model('article')->startCompress();
		foreach ($list as $article)
		{
			$this->model('article')->insert(array(
				'book_id' => $book->id,
				'content' => '',
				'title' => $article['name'],
				'url' => $article['url'],
				'completed' => 0,
			));
		}
		$this->model('article')->commitCompress();
	}
	
	/**
	 * 更新最新的文章列表
	 * 对于未完结的文章来说，只更新最新的文章列表
	 */
	function complete()
	{
		$books = $this->model('book')->where('completed=?',array(0))->select();
		foreach ($books as $book)
		{
			$book = new book($book);
			$list = $book->getNewArticle();
			if (!empty($list))
			{
				$this->model('article')->startCompress();
				foreach ($list as $article)
				{
					$this->model('article')->insert(array(
						'book_id' => $book->id,
						'content' => '',
						'title' => $article['name'],
						'url' => $article['url'],
						'completed' => 0,
					));
				}
				$this->model('article')->commitCompress();
			}
		}
	}
	 
	
	/**
	 * 文章下载
	 */
	function download()
	{
		$result = $this->model('article')->where('completed=?',array(0))->select();
		foreach ($result as $r)
		{
			echo "正在下载:《".$r['title']."》从：".$r['url'];
			$response = http::get($r['url']);
			echo "下载完成:《".$r['title']."》";
			
			if(preg_match('/<div id="content">(?<content>[\s\S]*)<\/div>/U', $response,$article))
			{
				$article = $article['content'];
				$article = iconv('gbk', 'utf-8', str_replace(array(
					'&nbsp;',
					' ',
				), '', strip_tags($article)));
				if (!empty($article))
				{
					if($this->model('article')->where('id=?',array($r['id']))->limit(1)->update(array(
						'content' => $article,
						'completed' => 1,
						'completed_time' => date('Y-m-d H:i:s'),
					)))
					{
						echo "保存成功:《".$r['title']."》";
					}
				}
				else
				{
					echo "下载错误:《".$r['title']."》（".$r['id']."）";
				}
			}
			else
			{
				echo "文章内容错误:《".$r['title']."》（".$r['id']."）";
			}
		}
	}
}
?>