<?php
namespace Wap\Controller\Mall;
use Wap\Controller\WapController;
use Common\Basic\Genre;

class BrandController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '品牌大全');
    }
	
    public function indexAction(){
    	$list = $this->goodsBrandService()->getAllList();
    	$this->assign('list', $list);
    	$this->display();
    }
    
    private function goodsBrandService(){
    	return D('GoodsBrand', 'Service');
    }
}