<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;
use Common\Basic\CsException;
use Common\Basic\SystemConst;

class AdminController extends FController {
	public function loginAction(){
		$post = I('post.');
		$adminService = $this->adminService();
		$params = array(
			'username'=>$post['phone'],
			'password'=>$post['password'],
		);
		$admin = $adminService->loginByPhone($params);
		$sess = $this->sessionService()->createAdminSession($admin['admin_id']);
		
		$data = array(
			'AdminInfo'=>$this->adminInfoOutput($admin),
			'Token'=>$sess,
			'Message'=>'登录成功',
		);
		$this->jsonReturn($data);
	}
	
	public function infoAction(){
		$post = I('post.');
		$admin_id = $post['admin_id'];
		if ($admin_id < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($admin_id);
		$data = array(
			'AdminInfo'=>$this->adminInfoOutput($admin),
		);
		$this->jsonReturn($data);
	}
	
	public function info_modifyAction(){
		$post = I('post.');
		$admin_id = $post['admin_id'];
		if ($admin_id < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($admin_id);
		$params = [];
		if(isset($post['email'])) $params['email'] = $post['email'];
		if(isset($post['avatar'])) $params['avatar'] = $post['avatar'];
		if(isset($post['sex'])) $params['sex'] = $post['sex'];
		if(isset($post['birthday'])) $params['sex'] = $post['birthday'];
		if(empty($params)){
			throw new CsException('未提交修改数据', 1001);
		}
		$params['admin_id'] = $admin['admin_id'];
		$this->adminService()->infoModify($params);
		$this->jsonReturn('修改成功');
	}
	
	public function pwd_by_phoneAction(){
		$post = I('post.');
		$params = array(
			'sms_id'=>$post['sms_id'],
			'password'=>$post['password'],
			'phone'=>$post['phone'],
			'code'=>$post['code'],
		);
		$this->adminService()->passwordModifyBySms($params);
		$data = array(
			'Message'=>'修改成功',
		);
		$this->jsonReturn($data);
	}
	
	public function departmentaddAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		$params = array(
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
			'department_name'=>$post['department_name'],
			'department_image'=>$post['department_image'],
		);
		$this->adminService()->departmentCreateOrModify($params);
		$data = array(
			'Message'=>'添加成功',
		);
		$this->jsonReturn($data);
	}
	
	public function departmenteditAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		$params = array(
			'department_id'=>$post['department_id'],
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
			'department_name'=>$post['department_name'],
			'department_image'=>$post['department_image'],
		);
		$this->adminService()->departmentCreateOrModify($params);
		$data = array(
			'Message'=>'修改成功',
		);
		$this->jsonReturn($data);
	}
	
	public function departmentdelAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		$params = array(
			'department_id'=>$post['department_id'],
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
		);
		$this->adminService()->departmentDelete($params);
		$data = array(
			'Message'=>'删除成功',
		);
		$this->jsonReturn($data);
	}
	
	public function departmentlistAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		$map = array(
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
		);
		$ll = $this->adminService()->departmentList($map);
		$list = array();
		foreach ($ll as $vo){
			$list[] = array(
				'DepartmentId'=>$vo['department_id'],
				'DepartmentName'=>$vo['department_name'],
				'DepartmentImage'=>picurl($vo['department_image']),
			);
		}
		$data = array(
			'List'=>$list,
		);
		$this->jsonReturn($data);
	}
	
	public function personlistAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		$map = array(
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
		);
		$ll = $this->adminService()->departmentList($map);
		$list = $person_list = array();
		foreach ($ll as $vo){
			$list[$vo['department_id']] = array(
				'DepartmentId'=>$vo['department_id'],
				'DepartmentName'=>$vo['department_name'],
				'DepartmentImage'=>picurl($vo['department_image']),
			);
		}
		
		$map = array(
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id'],
			'page'=>1,
			'pagesize'=>10000,
		);
		$result = $this->adminService()->adminPagerList($map);
		foreach ($result['list'] as $vo){
			if (empty($vo['departments'])){
				$vo['departments'] = '0';
			}
			$departments = explode(',', $vo['departments']);
			foreach ($departments as $po){
				$item = array(
					'AdminId'=>$vo['admin_id'],
					'AdminName'=>$vo['admin_name'],
					'Avatar'=>picurl($vo['avatar']),
				);
				if ($po > 0){
					$list[$po]['PersonList'][] = $item;
				} else {
					$person_list[] = $item;
				}
				
			}
		}
		
		$data = array(
			'List'=>array_values($list),
			'PersonList'=>$person_list,
		);
		$this->jsonReturn($data);
	}
	
	public function personinfoAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		if ($post['person_id'] < 1) throw new CsException('缺少参数', 11);
		$admin = $this->adminService()->getAdmin($post['admin_id']);
		
		$info = $this->adminService()->getAdmin($post['person_id']);
		$data = array(
			'Info'=>$this->adminInfoOutput($info),
		);
		$this->jsonReturn($data);
	}

	
	private function adminInfoOutput($admin){
		$info = array(
			'AdminId'=>$admin['admin_id'],
			'AdminName'=>$admin['admin_name'],
			'Avatar'=>picurl($admin['avatar']),
			'Sex'=>$admin['sex'],
			'Birthday'=>$admin['birthday'],
			'Mobile'=>$admin['mobile'],
			'Email'=>$admin['email'],
			'AddTime'=>$admin['add_time'],
			'Departments'=>array(),
			'CompanyName'=>'', //公司名
			'RoleName'=>'', //职位
			'RegionName'=>'',
			'AreaName'=>'',
			'Tags'=>['role'.$admin['role_id'].'@'.$admin['org_id']], //JPush
		);
		$role = $this->adminService()->getRole($admin['oa_role_id']);
		$info['RoleName'] = $role['role_name'];
		$departments = explode(',', $admin['departments']);
		if (is_array($departments) && count($departments) > 1){
			$map = array(
				'sys_id'=>$admin['sys_id'],
				'org_id'=>$admin['org_id'],
			);
			$ll = $this->adminService()->departmentList($map);
			foreach ($ll as $vo){
				if (in_array($vo['department_id'], $departments)){
					$info['Departments'][] = array(
						'DepartmentId'=>$vo['department_id'],
						'DepartmentName'=>$vo['department_name'],
						'DepartmentImage'=>picurl($vo['department_image']),
					);
				}
			}
		}
		if ($admin['sys_id'] == SystemConst::SysAdmin){
			$info['CompanyName'] = '谷安居';
		} elseif ($admin['sys_id'] == SystemConst::SysDistributor){
			$distributor = $this->distributorService()->getInfo($admin['org_id']);
			$info['CompanyName'] = $distributor['distributor_name'];
			$info['RegionName'] = $this->regionService()->getDistrictFullNameChange($distributor['region_code']);
			$area = $this->areaService()->getInfo($info['area_id']);
			$area && $info['AreaName'] = $area['area_name'];
		}
		return $info;
	}
	
	protected function adminService(){
		return D('Admin', 'Service');
	}
	
	protected function distributorService(){
		return D('Distributor', 'Service');
	}
	
	protected function regionService(){
		return D('Region', 'Service');
	}
	
	protected function areaService(){
		return D('Area', 'Service');
	}
	
	protected function smsService(){
		return D('Sms', 'Service');
	}
	
	private function sessionService(){
		return D('Session', 'Service');
	}
}