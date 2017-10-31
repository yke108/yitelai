<?php
namespace Admin\Controller\Recharge;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'recharge',
			'ac'=>'recharge_index_index',
		);
		$this->sbset($set);
		
	}
	public function indexAction(){
		
		$this->display();
	}
	
	
   
	
	private function PointService(){
		return D('Point',"Service");
	}
	private function UserSercice(){
		return D('User',"Service");
	}
	
	
	
}