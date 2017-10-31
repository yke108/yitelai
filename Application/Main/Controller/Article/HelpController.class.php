<?php
namespace Main\Controller\Article;
use Main\Controller\MainController;
use Common\Basic\Pager;

class HelpController extends MainController {
	private $newscatlist = array();
	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '帮助中心');
		
		$this->newscatlist = $this->newsinfoService()->catTreeList();
		$this->assign('newscatlist', $this->newscatlist);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	
    	$news_cat = $this->newsinfoService()->getCat($get['cat_id']);
    	if (empty($news_cat)) {
    		$map = array(
    				'parent_id'=>array('gt', 0)
    		);
    		$news_cat = $this->newsinfoService()->getCatInfo($map);
    		$get['cat_id'] = $news_cat['cat_id'];
    	}
    	$this->assign('news_cat', $news_cat);
    	$this->assign('get', $get);
    	
    	$pagesize = 8;
    	$params = array(
    			'cat_id'=>$news_cat['cat_id'],
    			'page'=>intval(I('p')) ? intval(I('p')) : 1
    	);
    	$datas = $this->newsinfoService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pager', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function infoAction($id = 0){
    	$info = $this->newsinfoService()->getInfo($id);
    	if(empty($info)) $this->error('内容不存在');
    	$this->assign('info', $info);
    	
    	$news_cat = $this->newsinfoService()->getCat($info['cat_id']);
    	$this->assign('news_cat', $news_cat);
    	
    	$this->display();
    }
    
    private function newsinfoService(){
    	return D('News', 'Service');
    }
}