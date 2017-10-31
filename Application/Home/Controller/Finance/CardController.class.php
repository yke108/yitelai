<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class CardController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction() {
    	$this->assign('page_title', '银行卡');
		$this->display();
    }
    
    public function addAction() {
    	$this->assign('page_title', '新增银行卡');
    	$this->display();
    }
}