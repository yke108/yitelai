<?php
namespace Admin\Controller\Admin;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class RoleController extends FController {
	protected $m_sys_id = 5;
	protected $m_org_id = 1;
	protected $d_sys_id = 2;
	protected $d_org_id = 2;
	
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
		
		//所属部门
		$params = array(
				'sys_id'=>$this->sys_id,
				'org_id'=>$this->org_id,
		);
		$department_list = $this->adminService()->departmentList($params);
		$this->assign('department_list', $department_list);
		
		$params = array(
    		'sys_id'=>$this->sys_id,
    	);
    	$purview_list = $this->systemService()->actionList($params);
		$this->assign('purview_list', $purview_list);
		
		//APP权限
		$params = array(
				'sys_id'=>$this->m_sys_id,
		);
		$purview_list = $this->systemService()->actionList($params);
		$this->assign('oa_purview_list', $purview_list);
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('角色不存在');
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
		
		//所属部门
		$params = array(
				'sys_id'=>$this->sys_id,
				'org_id'=>$this->org_id,
		);
		$department_list = $this->adminService()->departmentList($params);
		$this->assign('department_list', $department_list);
		
		$params = array(
    		'sys_id'=>$this->sys_id,
    	);
    	$purview_list = $this->systemService()->actionList($params);
		$this->assign('purview_list', $purview_list);
		
		$info['action_list'] = explode(',', $info['action_list']);
		$info['oa_action_list'] = explode(',', $info['oa_action_list']);
		$this->assign('info', $info);
		
		//APP权限
		$params = array(
				'sys_id'=>$this->m_sys_id,
		);
		$purview_list = $this->systemService()->actionList($params);
		$this->assign('oa_purview_list', $purview_list);
		
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('内容不存在');
		
		//特定角色不可删除
		if (in_array($id, array(58))) $this->error('角色不可删除');
		
		try {
			$params = array(
				'role_id'=>$info['role_id'],
				'sys_id'=>$this->sys_id,
				'org_id'=>$this->org_id,
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
	
	public function targetAction($id = 0) {
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('角色不存在');
		
		if (IS_POST) {
			$post = I('post.');
			
			$data = array(
					'role_ids'=>$post['role_ids'] ? implode(',', $post['role_ids']) : ''
			);
			
			try {
				$this->adminService()->roleModify($id, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功');
		}
		
		$info['role_ids'] = $info['role_ids'] ? explode(',', $info['role_ids']) : array();
		$this->assign('info', $info);
		
		//平台角色
		$params = array(
				'sys_id'=>$this->sys_id,
				'org_id'=>$this->org_id,
		);
		$role_list = $this->adminService()->roleList($params);
		$this->assign('role_list', $role_list);
		
		//店铺角色
		$params = array(
				'sys_id'=>$this->d_sys_id,
				'org_id'=>$this->d_org_id,
		);
		$role_list = $this->adminService()->roleList($params);
		$this->assign('distributor_role_list', $role_list);
		
		$this->display();
	}
	
	public function personnelAction($id = 0) {
		$info = $this->adminService()->getRole($id);
		if(empty($info)) $this->error('角色不存在');
		
		if (IS_POST) {
			$post = I('post.');
			
			$data = array(
					'personnel_role'=>$post['personnel_role'] ? implode(',', $post['personnel_role']) : ''
			);
			
			try {
				$this->adminService()->roleModify($id, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功');
		}
		
		$info['personnel_role'] = $info['personnel_role'] ? explode(',', $info['personnel_role']) : array();
		$this->assign('info', $info);
		
		//平台角色
		$params = array(
				'sys_id'=>$this->sys_id,
				'org_id'=>$this->org_id,
		);
		$role_list = $this->adminService()->roleList($params);
		$this->assign('role_list', $role_list);
		
		//店铺角色
		$params = array(
				'sys_id'=>$this->d_sys_id,
				'org_id'=>$this->d_org_id,
		);
		$role_list = $this->adminService()->roleList($params);
		$this->assign('distributor_role_list', $role_list);
		
		$this->display();
	}
}