<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Pager;

class GiftController extends MainController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    public function indexAction(){
    	$this->assign('page_title', '大牌明星产品');
    	$this->display();
    }
}