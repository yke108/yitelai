<?php
namespace Information\Controller\Admin;
use Information\Controller\FController;
use Common\Basic\Pager;

class ManagerController extends FController {
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'admin',
			'ac'=>'admin_manager_index',
		);
		$this->sbset($set);
		$p = array(
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
		);
		$role = $this->adminService()->roleList($p);
		$this->assign('role_list', $role);
    }
	
    public function indexAction($id = 0){
    	$this->purviewCheck('admin_index');
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'is_admin'=>0,
    		'sys_id'=>$this->sys_id,
    		'org_id'=>$this->org_id,
    	);
    	$result = $this->adminService()->adminPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->display();
    }
	
	public function addAction(){
		$this->purviewCheck('admin_add');
		if (IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			$params['departments'] = implode(',', $params['departments']);
			try {
				$this->adminService()->adminCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$params = array(
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
		);
		$list = $this->adminService()->departmentList($params);
		$this->assign('department_list', $list);
		$this->display();
	}	
	
	public function editAction($id = 0){
		$this->purviewCheck('admin_edit');
		$info = $this->adminService()->getAdmin($id);
		if(empty($info) || $info['is_admin'] > 0){
			$this->error('用户不存在');
		}
		if($info['admin_id'] == session('uid')){
			$this->error('你不能通过此处修改自己的资料');
		}
		if (IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			$params['admin_id'] = $id;
			$params['departments'] = implode(',', $params['departments']);
			try {
				$this->adminService()->adminCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$info['departments'] = explode(',', $info['departments']);
		$this->assign('info', $info);
		$params = array(
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
		);
		$list = $this->adminService()->departmentList($params);
		$this->assign('department_list', $list);
		$this->display('add');
	}
	
	public function delAction($id = 0){
		$this->purviewCheck('admin_del');
		$info = $this->adminService()->getAdmin($id);
		if(empty($info) || $info['is_admin'] > 0){
			$this->error('用户不存在');
		}

		if($info['admin_id'] == session('uid')){
			$this->error('你不能删除自己');
		}
		M('Admin')->where('admin_id=%d', $id)->delete();
		$this->success('删除成功');
	}
}