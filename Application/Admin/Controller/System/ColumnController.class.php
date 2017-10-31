<?php
namespace Admin\Controller\System;
use Admin\Controller\FController;
use Common\Basic\Pager;

class ColumnController extends FController {
	protected $m_sys_id = 1;
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_column_index',
		);
		$this->sbset($set);
	}
	
    public function indexAction($id = 0){
		$columnlist= $this->systemService()->columnList($this->m_sys_id);
		$this->assign('column_list', $columnlist);
		$this->display('System/Column/index');
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->SystemService()->columnCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->assign('menu_list', $this->systemService()->menuList($this->m_sys_id, 'all'));
		$this->display('System/Column/edit');
	}
	
	public function editAction($id = 0){
		$info = $this->SystemService()->getColumn($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['column_id'] = $info['column_id'];
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->SystemService()->columnCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$info['menu_list'] = explode(',', $info['menu_list']);
		$this->assign('info', $info);
		$this->assign('menu_list', $this->systemService()->menuList($this->m_sys_id, 'all'));
		$this->display('System/Column/edit');
	}
	
	public function delAction($id = 0){
		$info = $this->SystemService()->getColumn($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$params = array(
				'column_id'=>$info['column_id'],
				'sys_id'=>$this->m_sys_id,
			);
			$result = $this->SystemService()->columnDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
}