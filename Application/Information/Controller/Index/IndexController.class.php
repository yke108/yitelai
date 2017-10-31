<?php
namespace Information\Controller\Index;
use Information\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();

		$set = array(
			'in'=>'static',
			'ac'=>'index_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$this->display();
    }
	
}