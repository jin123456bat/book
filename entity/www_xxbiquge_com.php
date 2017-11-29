<?php
namespace book\entity;

use framework\core\entity;
use framework\core\http;
use framework\vendor\image;

class www_xxbiquge_com extends www_booktxt_net
{

	function __construct($data)
	{
		entity::__construct($data);
		
		if (! empty($this->_data['url']) && ! empty($this->_data['source']))
		{
			$this->_content = http::get($this->_data['url']);
			$this->_content = str_replace('&nbsp;', '', $this->_content);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getTitle()
	 */
	function getTitle()
	{
		return parent::getTitle();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getAuthor()
	 */
	function getAuthor()
	{
		return parent::getAuthor();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getDescription()
	 */
	function getDescription()
	{
		return parent::getDescription();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getIsCompleted()
	 */
	function getIsCompleted()
	{
		return preg_match('/<p>状\s态：连载中/', $this->_content);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getImage()
	 */
	function getImage()
	{
		if (preg_match('/<div id="fmimg"><img alt="[^"]*" src="(?<image>[^"]*)"/', $this->_content, $match))
		{
			$image = new image($match['image']);
			return $image->move(APP_ROOT . '/upload/' . date('Y-m-d') . '/')->rename(uniqid())->path(false);
		}
	}

	function getArticleList()
	{
		if (preg_match_all('/<dd><a href="(?<url>[^"]*)"( class="empty")?>(?<name>[^<]*)<\/a><\/dd>/', $this->_content, $match))
		{
			$temp = array();
			foreach ($match['url'] as $index => $url)
			{
				$name = $match['name'][$index];
				$temp[] = array(
					'url' => $this->_data['source'] . $url,
					'name' => $name
				);
			}
			return $temp;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \book\entity\www_booktxt_net::getNewArticle()
	 */
	function getNewArticle()
	{
		$list = $this->getArticleList();
		
		$name = array_column($list, 'name');
		
		$title = $this->model('article')->where('book_id=? and isdelete=?', array(
			$this->_data['id'],
			0
		))->column('title');
		
		$temp = array();
		for ($i = count($list) - 1; $i >= 0; $i --)
		{
			if (! in_array($list[$i]['name'], $title, true))
			{
				$temp[] = $list[$i];
			}
			else
			{
				break;
			}
		}
		return $temp;
	}

	/**
	 *
	 * @param unknown $url
	 * @return mixed
	 */
	static function getArticleContent($url)
	{
		$response = http::get($url);
		if (preg_match('/<div id="content">(?<content>[\s\S]*)<\/div>/U', $response, $article))
		{
			$content = $article['content'];
			$content = str_replace(array(
				'<br><br>',
				'<br/><br/>',
				'<br /><br />'
			), "\n\r", $content);
			$content = str_replace(array(
				'&nbsp;'
			), "", $content);
			return $content;
		}
	}
}