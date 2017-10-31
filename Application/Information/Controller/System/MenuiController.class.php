<?php
namespace Information\Controller\System;

class MenuiController extends MenuController {
	public function _initialize(){
		$this->m_sys_id = 3;
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_menui_index',
		);
		$this->sbset($set);
	}
}