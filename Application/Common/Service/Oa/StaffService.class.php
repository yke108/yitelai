<?php
namespace Common\Service\Oa;

use Common\Basic\CsException;
use Common\Basic\SystemConst;
use Common\Basic\Status;

class StaffService{
	
	public function salaryInfo($params, $role_filed = 'personnel_role'){
		//获取当前管理员
		$map = [
			'admin_id'=>$params['admin_id'],
		];
		$admin_field = array('admin_id', 'admin_name', 'is_admin', 'role_id', 'sys_id', 'org_id');
		$adminFind = $this->adminInfoDao()->where($map)->field($admin_field)->find();
		if (empty($adminFind)){
			throw new CsException('系统错误', 1001);
		}
		//获取当前管理员的角色
		if ($adminFind['is_admin'] == Status::YES){
			$personnel_roles = false;
		} else {
			$current_admin_role = $this->adminRoleDao()->getRecord($adminFind['role_id']);
			if (empty($current_admin_role) || empty($current_admin_role[$role_filed])){
				throw new CsException('没有操作权限', 1003);
			}
			$personnel_roles = explode(',', trim($current_admin_role[$role_filed], ','));
			if (count($personnel_roles) < 1){
				throw new CsException('没有操作权限', 1004);
			}
		}
		//获取有相应的角色列表
		$map = [];
		$personnel_roles !== false && $map['role_id'] = ['in', $personnel_roles];
		$role_list = $this->adminRoleDao()->where($map)->getField('role_id, department_id, role_name, sys_id');
		//获取:目标数据的系统和公司
		$content = '';
		if ($adminFind['sys_id'] == SystemConst::SysAdmin){
			if ($params['distributor_id'] > 0){
				$sys_id = SystemConst::SysDistributor;
				$org_id = $params['distributor_id'];
				if($params['content']){
					$content = $params['content'];
				}
				//TODO 增加区域限制
			} else {
				$sys_id = SystemConst::SysAdmin;
				$org_id = 1;
			}
		} else {
			$sys_id = SystemConst::SysDistributor;
			$org_id = $adminFind['org_id'];
		}
		//获取：对应的全部部门
		$map = [
			'sys_id'=>$sys_id,
			'role_id'=>['in', array_keys($role_list)],
		];
		$departments = $this->adminDepartmentDao()->where($map)
		->getField('department_id, department_name, department_image');
		//获取：符合条件的人员
		$map = [
			'sys_id'=>$sys_id,
			'role_id'=>['in', array_keys($role_list)],
			'org_id'=>$org_id,
			'admin_name'=>['like', '%'.$content.'%'],
		];
		$person_list = $this->adminInfoDao()->where($map)
		->field('admin_id, admin_name, avatar, role_id, mobile')->select();
		//组织数据
		$department_list = [];
		foreach ($person_list as $vo){
			$role = $role_list[$vo['role_id']];
			$dpid = $role['department_id'];
			if (empty($department_list[$dpid])){
				$department = $departments[$dpid];
				$department_list[$dpid] = $department;
			}
			$vo['role_name'] = $role['role_name'];
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$department_list[$dpid]['personnel'][] = $vo;
		}
		return [
			'department_list' => $department_list,
			'admin_sys_id'=>$adminFind['sys_id'],
		];
	}
	
	//调用model
	private function feedbackDao(){
		return D('Common/Feedback/Feedback');
	}

	private function goodsBrandService() {
		return D('Common/Goods/GoodsBrand');
	}

	private function feedbackReplyDao(){
		return D('Common/Feedback/FeedbackReply');
	}
	
	private function feedbackAppDao(){
		return D('Common/Feedback/FeedbackApp');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function adminDepartmentDao(){
		return D('Common/Admin/AdminDepartment');
	}
	
	private function adminRoleDao(){
		return D('Common/Admin/AdminRole');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
}//end FeedbackService!