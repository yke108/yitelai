<?php
namespace Admin\Controller\Admin;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DepartmentController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'admin',
			'ac'=>'admin_department_index',
		);
		$this->sbset($set);
		
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'sys_id'=>$this->sys_id,
    		'org_id'=>$this->org_id,
    	);
    	$list = $this->adminService()->departmentList($params);
    	$this->assign('list', $list);
		$this->assign('get', $get);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			try {
				$result = $this->adminService()->departmentCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->adminService()->getDepartment($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->sys_id;
			$params['org_id'] = $this->org_id;
			$params['department_id'] = $info['department_id'];
			try {
				$result = $this->adminService()->departmentCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->adminService()->getDepartment($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$params = array(
				'department_id'=>$info['department_id'],
			);
			$result = $this->adminService()->departmentDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
}