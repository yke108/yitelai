<?php
namespace Wap\Controller\Article;
use Wap\Controller\WapController;

class PageController extends WapController {
	public function _initialize(){
		parent::_initialize();
    }
	
    public function infoAction($id = 0){
    	$info = $this->pageService()->getInfo($id);
    	if(empty($info)) $this->error('内容不存在');
    	$this->assign('info', $info);
    	$this->display();
    }
    
    public function serviceAction($service_id = 0){
    	$info = $this->goodsServiceService()->getInfo($service_id);
    	if(empty($info)) $this->error('内容不存在');
    	$this->assign('info', $info);
    	$this->display();
    }
    
    private function pageService(){
    	return D('Page', 'Service');
    }
    
    private function goodsServiceService(){
    	return D('GoodsService', 'Service');
    }
}