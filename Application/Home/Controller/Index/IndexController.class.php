<?php
namespace Home\Controller\Index;
use Home\Controller\BaseController;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$admin = $this->adminService()->getAdmin(session('uid'));
		$this->display();
    }
    
    public function saleAction() /*业务员标记*/ { /*仅用作权限管理*/ }
}