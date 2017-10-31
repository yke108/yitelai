<?php
namespace Admin\Controller\System;

class MenugController extends MenuController {
	public function _initialize(){
		$this->m_sys_id = 4;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_menug_index',
		);
		$this->sbset($set);
	}
}