<?php
namespace Admin\Controller\System;
use Admin\Controller\FController;
use Common\Basic\Pager;

class ActionController extends FController {
	protected $m_sys_id = 1;
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'menu',
			'ac'=>'system_action_index',
		);
		$this->sbset($set);
    	$action_list=$this->systemService()->actionPagerList(array('page'=>1,'pagesize'=>1000));
		foreach($action_list['list'] as $key=>$val){
			$new_list[$val['action_id']]=$val;
		}
		$this->assign('action_list',$new_list);
		$top_action_list=$this->systemService()->topActionList($this->m_sys_id);
		$this->assign('top_action_list',$top_action_list);
		
	}
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'sys_id'=>$this->m_sys_id,
    	);
		
    	$list = $this->systemService()->actionList($params);
		$this->assign('list', $list);
		$this->display('System/Action/index');
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->systemService()->actionCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->display('System/Action/edit');
	}
	
	public function editAction($id = 0){
		$info = $this->systemService()->getAction($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['action_id'] = $info['action_id'];
			$params['sys_id'] = $this->m_sys_id;
			try {
				$result = $this->systemService()->actionCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$info['_list'] = explode(',', $info['_list']);
		$this->assign('info', $info);
		$this->display('System/Action/edit');
	}
	
	public function delAction($id = 0){
		$info = $this->systemService()->getAction($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$params = array(
				'action_id'=>$info['action_id'],
				'sys_id'=>$this->m_sys_id,
			);
			$result = $this->systemService()->actionDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
}