<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class LifeController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		
		$this->display();
    }
}