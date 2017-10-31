<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class HelpController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		//帮助中心
		$cat_id = intval($post['cat_id']);
		$list = array();
		
		$articleService = $this->articleService();
		
		$tl = $articleService->findChildCategories($cat_id);
		foreach($tl as $vo){
			$list[] = array(
					'CatId'=>$vo['cat_id'],
					'Title'=>$vo['cat_name'],
					'IsCategory'=>1,
			);
		}
		
		$tl = $articleService->findArticlesOfCategory($cat_id);
		foreach($tl as $vo){
			$list[] = array(
					'ArticleId'=>$vo['article_id'],
					'Title'=>$vo['article_title'],
					'Url'=> apiUrlToPage(U('article/index', array('id'=>$vo['article_id']))),
					'IsCategory'=>0,
			);
		}
		$this->assign('list', $list);
		
		$this->display();
    }
}