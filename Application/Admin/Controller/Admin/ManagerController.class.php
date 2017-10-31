<?php
namespace Admin\Controller\Admin;
use Admin\Controller\FController;
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
		
		$p = array(
			'sys_id'=>5,
			'org_id'=>1,
		);
		$role = $this->adminService()->roleList($p);
		$this->assign('oa_role_list', $role);
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
		$params = array(
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
		);
		$list = $this->adminService()->adminList($params);
		$this->assign('admin_list', $list);
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
		$params = array(
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
			'admin_id'=>['neq', $info['admin_id']],
		);
		$list = $this->adminService()->adminList($params);
		$this->assign('admin_list', $list);
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


	//人中管理
	public function listsAction(){
		$set = array(
			'in'=>'finance',
			'ac'=>'admin_manager_lists',
		);
		$this->sbset($set);
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

	//历史工资列表
	public function commissionlistAction(){
		session('back_url', __SELF__);
		$set = array(
			'in'=>'finance',
			'ac'=>'admin_manager_lists',
		);
		$this->sbset($set);
		$this->purviewCheck('admin_index');
		$id = I('get.id');
		$data = array();
		$data['admin_id'] = $id;
		$data['page'] = intval(I('p')) > 0 ? intval(I('p')) : 1;
		$data['pagesize'] = $this->pagesize;
		$result = $this->adminService()->commissionList($data);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $result['pagesize']);
			$this->assign('pager', $pager->show());
		}
		$adminFind = D('Admin')->where(array('admin_id' => $id))->field(array('admin_name'))->find();
		$this->assign('adminFind', $adminFind);
		$this->display();
	}

	//发放工资
	public function grantAction(){
		$id = I('get.id');
		try {
			$where = array();
			$data = array();
			$where['admin_wage_id'] = $id;
			$data['status'] = 1;
			$this->adminService()->adminWageGrantService($where, $data);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('修改成功', session('back_url'));
	}

	//提成
	public function commissionAction()
	{
		if (IS_POST) {
			$params = I('post.');
			$data = array();
			$data['sys_id'] = $this->sys_id;
			$data['org_id'] = $this->org_id;
			$data['admin_id'] = $params['admin_id'];
			$data['commission_rate'] = $params['commission_rate'];
			try {
				$this->adminService()->updateService($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		} else {
			$id = I('get.id');
			$this->purviewCheck('admin_commission');
			$info = $this->adminService()->getAdmin($id);
			if (empty($info) || $info['is_admin'] > 0) {
				$this->error('人员不存在');
			}
			$this->assign('info', $info);
			$this->display();
		}
	}

	//发放工资
	public function payrollAction(){
		if (IS_POST) {
			$params = I('post.');
			$data = array();
			$data['admin_id'] = $params['admin_id'];
			$data['inputtime'] = strtotime($params['inputtime']);
			$data['month'] = $params['month'];
			$data['post_salary'] = $params['post_salary'];
			$data['legal_wage'] = $params['legal_wage'];
			$data['assessment_wage'] = $params['assessment_wage'];
			$data['reward'] = $params['reward'];
			$data['commission'] = $params['commission'];
			$data['commission_total'] = $params['commission_total'];
			$data['travel_expenses'] = $params['travel_expenses'];
			$data['office_expenses'] = $params['office_expenses'];
			$data['status'] = $params['status'];
			$data['total_wages'] = $params['post_salary'] + $params['legal_wage'] + $params['assessment_wage'] + $params['reward'] + $params['commission'] + $params['travel_expenses'] + $params['office_expenses'];
			try {
				$this->adminService()->addAdminWage($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		} else {
			$this->assign('payroll_month', date('Y-m'));
			$this->assign('inputtime', date('Y-m-d H:i:s'));
			$this->assign('month_list', this_last_year());
			$id = I('get.id');
			$this->purviewCheck('admin_commission');
			$info = $this->adminService()->getAdmin($id);
			if (empty($info) || $info['is_admin'] > 0) {
				$this->error('人员不存在');
			}
			$this->assign('admin_id', $info['admin_id']);
			$this->assign('admin_name', $info['admin_name']);
			$this->assign('commission_rate', $info['commission_rate']);
			$this->assign('commission_value', ($info['commission_rate']/100));
			$this->display();
		}
	}

	public function updatepayrollAction(){
		if (IS_POST) {
			$params = I('post.');
			$where = array();
			$data = array();
			$where['admin_wage_id'] = $params['admin_wage_id'];
			$data['inputtime'] = strtotime($params['inputtime']);
			$data['month'] = $params['month'];
			$data['post_salary'] = $params['post_salary'];
			$data['legal_wage'] = $params['legal_wage'];
			$data['assessment_wage'] = $params['assessment_wage'];
			$data['reward'] = $params['reward'];
			$data['commission'] = $params['commission'];
			$data['commission_total'] = $params['commission_total'];
			$data['travel_expenses'] = $params['travel_expenses'];
			$data['office_expenses'] = $params['office_expenses'];
			$data['status'] = $params['status'];
			$data['total_wages'] = $params['post_salary'] + $params['legal_wage'] + $params['assessment_wage'] + $params['reward'] + $params['commission'] + $params['travel_expenses'] + $params['office_expenses'];
			try {
				$this->adminService()->updateAdminWage($where, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		} else {
			$this->assign('payroll_month', date('Y-m'));
			$this->assign('inputtime', date('Y-m-d H:i:s'));
			$this->assign('month_list', this_last_year());
			$id = I('get.id');
			$this->purviewCheck('admin_commission');
			$adminWageFind = $this->adminService()->adminWageFind($id);
			if (empty($adminWageFind)) $this->error('发生致命错误');
			$info = $this->adminService()->getAdmin($adminWageFind['admin_id']);
			if (empty($info) || $info['is_admin'] > 0) $this->error('人员不存在');
			$this->assign('adminWageFind', $adminWageFind);
			$this->assign('admin_id', $info['admin_id']);
			$this->assign('admin_name', $info['admin_name']);
			$this->assign('commission_rate', $info['commission_rate']);
			$this->assign('commission_value', ($info['commission_rate'] / 100));
			$this->display();
		}
	}
}