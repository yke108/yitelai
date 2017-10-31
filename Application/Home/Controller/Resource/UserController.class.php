<?php
namespace Home\Controller\Resource;
use Home\Controller\BaseController;

class UserController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
		$this->display();
    }
}