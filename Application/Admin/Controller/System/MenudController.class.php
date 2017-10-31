<?php
namespace Admin\Controller\System;

class MenudController extends MenuController {
	public function _initialize(){
		$this->m_sys_id = 2;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_menud_index',
		);
		$this->sbset($set);
	}
}