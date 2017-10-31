<?php
namespace Admin\Controller\System;

class MenubController extends MenuController {
	public function _initialize(){
		$this->m_sys_id = 5;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_menub_index',
		);
		$this->sbset($set);
	}
}