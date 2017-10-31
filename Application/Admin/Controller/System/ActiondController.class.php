<?php
namespace Admin\Controller\System;

class ActiondController extends ActionController {
	public function _initialize(){
		$this->m_sys_id = 2;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_actiond_index',
		);
		$this->sbset($set);
	}
}