<?php
namespace Gallery\Controller\System;
use Gallery\Controller\FController;
use Common\Basic\Pager;

class MenuController extends FController {
	protected $m_sys_id = 1;
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_menu_index',
		);
		$this->sbset($set);
		$this->assign('top_menu_list',$this->systemService()->topMenuList($this->m_sys_id));
		$this->assign('icon_list',$this->SystemService()->menuIcons());
	}
	
    public function indexAction($id = 0){
		$menulist= $this->systemService()->menuList($this->m_sys_id, 'all');
		$this->assign('menu_list', $menulist);
		$this->display('System/Menu/index');
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->SystemService()->menuCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->display('System/Menu/edit');
	}
	
	public function editAction($id = 0){
		$info = $this->SystemService()->getMenu($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['menu_id'] = $info['menu_id'];
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->SystemService()->menuCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$info['_list'] = explode(',', $info['_list']);
		$this->assign('info', $info);
		$this->display('System/Menu/edit');
	}
	
	public function delAction($id = 0){
		$info = $this->SystemService()->getMenu($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$params = array(
				'menu_id'=>$info['menu_id'],
				'sys_id'=>$this->m_sys_id,
			);
			$result = $this->SystemService()->menuDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
}