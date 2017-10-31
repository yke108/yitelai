<?php
namespace News\Controller\User;
use News\Controller\WapController;

class IndexController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		
    	$this->assign('page_title', '个人中心');
		$this->display();
    }
	
	private function orderService() {
		return D('Order', 'Service');
	}
}
