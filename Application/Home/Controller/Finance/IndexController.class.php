<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*财务管理 */
    	$this->assign('sys_id', session('sys_id'));
    	
    	$this->assign('page_title', '财务管理');
		$this->display();
    }
}