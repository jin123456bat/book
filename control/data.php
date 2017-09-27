<?php 
namespace book\control;
use framework\core\control;
use framework\core\http;
use book\entity\book;
use framework\core\request;
use framework\core\response\url;

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
		
		$query = isset($url['query']) && !empty($url['query'])?$url['query']:'';
		$path = isset($url['path']) && !empty($url['path'])?$url['path']:'';
		//主机host
		$host = $url['scheme'].'://'.$url['host'];
		//目录url
		$url = $host.$path.$query;
		
		$data = array(
			'url' => $url,
			'source' => $host,
		);
		$book = new book($data);
		
		//更新基础信息
		$book->name = $book->getTitle();
		$book->author = $book->getAuthor();
		$book->description = $book->getDescription();
		$book->completed = $book->getIsCompleted()?1:0;
		$book->isdelete=0;
		$book->download_completed = 0;
		$book->image = $book->getImage();
		if ($book->validate())
		{
			if($book->save())
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
						'completed' => 0,
					));
				}
				$this->model('article')->commitCompress();
				return new \framework\core\response\message('添加成功',\framework\core\http::url('admin', 'index'));
			}
			else
			{
				return new \framework\core\response\message('添加失败',\framework\core\http::url('admin', 'index'));
			}
		}
		else
		{
			$error = $book->getError();
			return new \framework\core\response\message($error['name'][0],\framework\core\http::url('admin', 'index'));
		}
		
	}
	
	/**
	 * 更新最新的文章列表
	 * 对于未完结的文章来说，只更新最新的文章列表
	 */
	function complete()
	{
		$books = $this->model('book')->where('completed=? and isdelete=?',array(0,0))->select();
		//$books = $this->model('book')->where('id=?',[1])->select();
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
				
				$book->completed = $book->getIsCompleted()?1:0;
			}
		}
		echo "同步完成";
	}
	
	/**
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
	 * 文章下载
	 */
	function download()
	{
		$result = $this->model('article')->where('completed=? and isdelete=?',array(0,0))->limit(100)->select();
		//$result = $this->model('article')->where('id=?',array(1225))->select();
		foreach ($result as $r)
		{
			$response = http::get($r['url']);
			
			if(preg_match('/<div id="content">(?<content>[\s\S]*)<\/div>/U', $response,$article))
			{
				$article_content = $article['content'];
				$content = iconv('gbk', 'utf-8//TRANSLIT', str_replace(array(
					'&nbsp;',
					' ',
				), '', strip_tags($article_content)));
				if (!empty($content))
				{
					if($this->model('article')->where('id=?',array($r['id']))->limit(1)->update(array(
						'content' => $content,
						'completed' => 1,
						'completed_time' => date('Y-m-d H:i:s'),
					)))
					{
						echo "下载完成:《".$r['title']."》";
					}
				}
			}
		}
	}
}
?>