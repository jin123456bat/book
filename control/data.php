<?php 
namespace book\control;
use framework\core\Control;
use framework\core\database\mysql\field;
use framework\core\http;
use framework\vendor\image;

class data extends Control
{
	/**
	 * 添加书籍
	 */
	function create()
	{
		//主机host
		$host = 'http://www.booktxt.net';
		//目录url
		$url = $host.'/2_2219/';
		
		$content = http::get($url);
		if (!empty($content))
		{
			/* $table = $this->table('book')->drop();
			if(!$table->exist())
			{
				$table->field('id')->int()->comment('文章ID')->autoIncrement();
				$table->field('name')->varchar(256)->charset('utf8')->comment('书名');
				$table->field('author')->varchar(256)->charset('utf8')->comment('作者');
				$table->field('completed')->tinyint(1)->default(0)->comment('是否完结');
				$table->field('isdelete')->tinyint(1)->default(0)->comment('是否删除');
				$table->field('download_completed')->tinyint(1)->default(0)->comment('是否已经同步完成');
				$table->field('createtime')->datetime()->default(field::DEFAULT_CURRENT_TIMESTAMP)->comment('创建时间');
				$table->field('url')->text()->comment('数据来源url');
				$table->field('image')->varchar(256)->default('')->comment('封面图');
				$table->field('source')->varchar(128)->comment('数据来源');
				$table->index('name')->unique()->comment('电子书只能有一本，重复添加无效');
				//$table->primary()->add('id');//ID作为主键
			}
			$table = $this->table('article')->drop();
			if (!$table->exist())
			{
				$table->field('id')->int()->autoIncrement()->comment('文章id');
				$table->field('title')->varchar(256)->charset('utf8')->comment('文章标题');
				$table->field('book_id')->int()->comment('文章ID');
				$table->field('content')->longtext()->comment('文章内容');
				$table->field('isdelete')->tinyint(1)->default(0)->comment('是否删除');
				$table->field('createtime')->datetime()->default(field::DEFAULT_CURRENT_TIMESTAMP)->comment('创建时间');
				$table->field('url')->text()->comment('数据来源url');
				$table->field('completed')->tinyint(1)->default(0)->comment('是否已经下载');
				$table->field('completed_time')->datetime()->default(field::DEFAULT_CURRENT_TIMESTAMP)->comment('下载完成时间');
			}
			 */
			//去掉html空格
			$content = str_replace('&nbsp;', '', $content);
			$content = iconv('gbk', 'utf-8', $content);
			
			preg_match('/<h1>(?<title>.*)<\/h1>/', $content,$match);
			//书名
			$title = $match['title'];
			
			preg_match('/<p>作\S*者：(?<author>.+)<\/p>/', $content,$match);
			//作者名
			$author= $match['author'];
			
			//是否完结
			preg_match('/<span class="(?<completed>[a-z])"><\/span>/', $content,$match);
			$completed = $match['completed'] == 'a'?1:0;
			
			//封面图
			preg_match('/<div id="fmimg"><script src="(?<img>.+)"><\/script><span.*><\/span><\/div>/', $content,$match);
			$response = http::get($host.$match['img']);
			preg_match('/src=\'(?<image>[^\']+)\'/', $response,$image);
			$image = $image['image'];
			
			//添加书籍
			//$this->model('book')->transaction();
			if($this->model('book')->insert(array(
				'name' => $title,
				'author' => $author,
				'completed' => $completed,
				'url' => $url,
				'source' => $host,
				'image' => $image,
			)))
			{
				$book_id = $this->model('book')->lastInsertId();
				//更新书籍封面
				$image = new image($image);
				$this->model('book')->where('id=?',array($book_id))->limit(1)->update('image',$image->move(APP_ROOT.'/upload/'.date('Y-m-d').'/')->rename($book_id)->path(false));
				
				preg_match_all('/<dd><a href="(?<url>.*)">(?<name>.*)<\/a><\/dd>/', $content,$match);
				
				$this->model('article')->startCompress();
				foreach ($match['url'] as $index => $url)
				{
					$name = $match['name'][$index];
					$article = '';
					//添加文章
					$this->model('article')->insert(array(
						'book_id' => $book_id,
						'content' => $article,
						'title' => $name,
						'url' => $host.$url,
						'completed' => 0,
					));
				}
				$this->model('article')->commitCompress();
			}
			echo "下载完成";
		}
		else
		{
			echo "下载失败";
		}
	}
	
	/**
	 * 文章下载
	 */
	function downloadArticle()
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