<?php
namespace Admin\Controller\System;

class ActionappController extends ActionController {
	public function _initialize(){
		$this->m_sys_id = 5;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_actionapp_index',
		);
		$this->sbset($set);
	}
}