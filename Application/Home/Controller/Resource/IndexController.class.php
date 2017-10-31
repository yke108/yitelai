<?php
namespace Home\Controller\Resource;
use Home\Controller\BaseController;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*资源管理 */
    	$this->assign('page_title', '资源管理');
		$this->display();
    }
}