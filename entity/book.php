<?php
namespace book\entity;
use framework\core\entity;
use framework\core\http;
use framework\vendor\image;

class book extends entity
{
	private $_content;
	
	function __construct($data)
	{
		parent::__construct($data);
		
		if (!empty($this->_data['url']) && !empty($this->_data['source']))
		{
			$this->_content = http::get($this->_data['url']);
			$this->_content = str_replace('&nbsp;', '', $this->_content);
			$this->_content = iconv('gbk', 'utf-8', $this->_content);
		}
	}
	
	function __rules()
	{
		return array(
			'unique' => array(
				'fields' => 'name',
				'message' => '书籍《{value}》已经存在'
			)
		);
	}
	
	/**
	 * 获取文章标题
	 */
	function getTitle()
	{
		preg_match('/<h1>(?<title>.*)<\/h1>/', $this->_content,$match);
		return $match['title'];
	}
		
	/**
	 * 获取文章作者
	 * @return unknown
	 */
	function getAuthor()
	{
		preg_match('/<p>作\S*者：(?<author>.+)<\/p>/', $this->_content,$match);
		return $match['author'];	
	}
	
	/**
	 * 获取书籍描述
	 */
	function getDescription()
	{
		preg_match('/<div id="intro">[\s]*<p>(?<description>[\s\S]*)<\/p>[\s]*<\/div>/im',$this->_content,$match);
		return $match['description'];
	}
	
	/**
	 * 书籍是否完结
	 * @return boolean
	 */
	function getIsCompleted()
	{
		preg_match('/<span class="(?<completed>[a-z])"><\/span>/', $this->_content,$match);
		return $match['completed'] == 'a';
	}
	
	/**
	 * 获取封面图
	 * @return mixed
	 */
	function getImage()
	{
		preg_match('/<div id="fmimg"><script src="(?<img>.+)"><\/script><span.*><\/span><\/div>/', $this->_content,$match);
		$response = http::get($this->_data['source'].$match['img']);
		preg_match('/src=\'(?<image>[^\']+)\'/', $response,$image);
		
		$image = new image($image['image']);
		return $image->move(APP_ROOT.'/upload/'.date('Y-m-d').'/')->rename(uniqid())->path(false);
	}
	
	/**
	 * 获取已经现有的文章列表
	 * 一定要保持正序
	 * 返回的url必须是完整url  否则后面无法下载
	 */
	function getArticleList()
	{
		preg_match_all('/<dd><a href="(?<url>.*)">(?<name>.*)<\/a><\/dd>/', $this->_content,$match);
		$temp = array();
		foreach ($match['url'] as $index => $url)
		{
			$name = $match['name'][$index];
			$temp[] = array(
				'url' => $this->_data['source'].$url,
				'name' => $name
			);
		}
		return $temp;
	}
	
	/**
	 * 获取更新的文章列表
	 */
	function getNewArticle()
	{
		$list = $this->getArticleList();
		
		$count = $this->model('article')->where('book_id=?',array(
			$this->_data['id']
		))->count();
		if ($count == count($list))
		{
			return array();
		}
		
		$list = array_reverse($list);
		$list = array_slice($list, $count);
		return $list;
	}
}