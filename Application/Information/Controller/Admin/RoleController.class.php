<?php
namespace Information\Controller\Admin;
use Information\Controller\FController;
use Common\Basic\Pager;

class RoleController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'admin',
			'ac'=>'admin_role_index',
		);
		$this->sbset($set);
		
    }
	
    public function indexAction($id = 0){
    	
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'sys_id'=>$this->sys_id,
    		'org_id'=>$this->org_id,
    	);
    	$result = $this->adminService()->rolePagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->display();
    }
	
	public function addAction(){
		
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			try {
				$result = $this->adminService()->roleCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$params = array(
    		'sys_id'=>$this->sys_id,
    	);
    	$purview_list = $this->systemService()->actionList($params);
		$this->assign('purview_list', $purview_list);
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['role_id'] = $info['role_id'];
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			try {
				$result = $this->adminService()->roleCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$params = array(
    		'sys_id'=>$this->sys_id,
    	);
    	$purview_list = $this->systemService()->actionList($params);
		$this->assign('purview_list', $purview_list);
		$info['action_list'] = explode(',', $info['action_list']);
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$params = array(
				'role_id'=>$info['role_id'],
			);
			$result = $this->adminService()->roleDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function pall(){
		$params = array(
			'pagesize'=>1000,
			'sys_id'=>$this->sys_id,
		);
		$result=$this->systemService()->actionPagerList($params);
		$action_list=node_merge($result['list'],0,1,'action_id');
		foreach($action_list as $key=>$val){
			$list[$val['action_code']][0]=$val['action_name'];
			foreach($val['children'] as $c_key=>$c_val){
				$list[$val['action_code']][$c_val['action_code']]=$c_val['action_name'];
			}
		}
		
		return $list;
	}
}