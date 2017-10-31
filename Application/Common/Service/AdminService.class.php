<?php
namespace Common\Service;
use Common\Basic\CsException;
use Common\Basic\Status;

class AdminService{
	public function getAdmin($id){
		if ($id < 1) return false;
		$info = $this->adminInfoDao()->getRecord($id);
		return $this->outPutForInfo($info);
	}
	
	public function findAdmin($map){
		$info = $this->adminInfoDao()->findRecord($map);
		return $this->outPutForInfo($info);
	}

	public function updateService($map)
	{
		$where = array();
		$data = array();
		if ($map['sys_id']) {
			$where['sys_id'] = $map['sys_id'];
		}
		if ($map['org_id']) {
			$where['org_id'] = $map['org_id'];
		}
		if ($map['admin_id']) {
			$where['admin_id'] = $map['admin_id'];
		}
		if ($map['commission_rate']) {
			$data['commission_rate'] = $map['commission_rate'];
		}
		return $this->adminInfoDao()->updateAdmin($where, $data);
	}
	
	public function adminList($map){
		return $this->adminInfoDao()->where($map)->select();
	}
	
	public function adminCreateOrModify($params){
		$rules = array(
			array('username', 'require', '账号不能为空'),
			array('sys_id,username','','账号已经存在！',1,'unique'),
			//array('username','/^[a-z][0-9a-z_]{5,15}$/','非法账号！',0,'regex'), 
		    array('admin_name','require','姓名是必须的！'), 
			array('role_id','require','角色是必须的！'),
			array('mobile','/^1\d{10}$/','手机号为空或错误！',0,'regex'), 
			//array('email','email','请填写正确的Email！'), 
			array('password','require','密码是必须的！'),
			array('repassword','password','确认密码不正确',2,'confirm'),
		    array('mobile','','手机已经存在！',1,'unique'),
			//array('sys_id,email','','邮箱已经存在！',1,'unique'),
		);
		$data = array(
				'username'=>strtolower(trim($params['username'])),
				'admin_name'=>trim($params['name']),
				'sex'=>trim($params['sex']),
				'mobile'=>trim($params['mobile']),
				'email'=>strtolower(trim($params['email'])),
				'role_id'=>trim($params['role_id']),
				'oa_role_id'=>intval($params['oa_role_id']),
				'error_count'=>0,
				'departments'=>$params['departments'],
				'commission_rate'=>$params['commission_rate'], //提成比例
				'admin_type'=>$params['admin_type'], //管理员类型
		);
		if(!empty($params['password'])){
			$data['password'] = trim($params['password']);
			$data['repassword'] = trim($params['password2']);
		}
		if($params['admin_id'] > 0){
			$data['admin_id'] = $params['admin_id'];
		} else {
			$data['is_admin'] = intval($params['is_admin']) ? intval($params['is_admin']) : 0;
			$data['sys_id'] = $params['sys_id'];
			$data['org_id'] = $params['org_id'];
			$data['add_time'] = NOW_TIME;
		}
		$adminInfoDao = $this->adminInfoDao();
		if (!$adminInfoDao->validate($rules)->create($data)){
			 throw new \Exception($adminInfoDao->getError());
		}
		unset($data['repassword']);
		//权限
		if ($data['is_admin'] != 1){
			$role = $this->adminRoleDao()->getRecord($data['role_id']);
			if($role['action_list']) $data['action_list'] = $role['action_list'];
			if($role['oa_action_list']) $data['oa_action_list'] = $role['oa_action_list'];
		} else {
			$data['action_list'] = 'all';
		}
		
		//上级
		if ($params['parent_id'] > 0){
			$adminp = $this->adminInfoDao()->getRecord($params['parent_id']);
			if (empty($adminp) 
					|| $adminp['sys_id'] != $params['sys_id']
					|| $adminp['org_id'] != $params['org_id']){
				throw new CsException('上级管理员不存在',1003);
			}
			$data['parent_id'] = $adminp['admin_id'];
		}
		
		//App权限
		$role = $this->adminRoleDao()->getRecord($data['oa_role_id']);
		if($role['action_list']) $data['oa_action_list'] = $role['action_list'];
		
		M()->startTrans();
		if ($params['admin_id'] > 0){
			//管理员关联相同手机号的前端用户（用于OA模块视频记录和图片记录）
			$admin_info = $this->getAdmin($params['admin_id']);
			if ($admin_info['mobile'] != $data['mobile']) {
				$map = array('mobile'=>$data['mobile']);
				$user_info = $this->userInfoDao()->findUser($map);
				$data['user_id'] = $user_info ? $user_info['user_id'] : '';
			}
			
			$result = $adminInfoDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			$admin_id = $params['admin_id'];
		} else {
			//管理员关联相同手机号的前端用户（用于OA模块视频记录和图片记录）
			$map = array('mobile'=>$data['mobile']);
			$user_info = $this->userInfoDao()->findUser($map);
			$data['user_id'] = $user_info ? $user_info['user_id'] : '';
			
			$admin_id = $adminInfoDao->addRecord($data);
			if ($admin_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//分销商关联管理员ID
		if ($params['org_id'] > 0) {
			$result = $this->distributorInfoDao()->where(array('distributor_id'=>$params['org_id']))->save(array('admin_id'=>$admin_id));
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		M()->commit();
	}
	
	public function infoModify($params){
		$adminInfoDao = $this->adminInfoDao();
		$result = $adminInfoDao->saveRecord($params);
		if ($result === false){
			throw new CsException('修改失败');
		}
	}
	
	public function passwordModifyBySms($params){
		if (empty($params['sms_id']) || empty($params['code']) || empty($params['phone'])){
			throw new CsException('系统错误', 11);
		}
		if (empty($params['password'])) throw new CsException('密码不能为空', 2013);
		$smsCodeDao = $this->smsCodeDao();
		$sms = $smsCodeDao->getRecord($params['sms_id']);
		if(empty($sms) || $sms['code'] != $smsCodeDao->psd($params['code']) || $sms['phone'] != $params['phone']){
			throw new CsException('验证码不正确', '10007');
		}
		if ($sms['expire_time'] < NOW_TIME) {
			throw new CsException('验证码已过期', '10008');
		}
		$adminInfoDao = $this->adminInfoDao();
		$map = array(
			'mobile'=>$sms['phone'],
		);
		$admin = $adminInfoDao->findRecord($map);
		if (empty($admin)) throw new CsException('用户不存在', '10023');
		
		$data = array(
			'admin_id'=>$admin['admin_id'],
			'password'=>trim($params['password']),
			'error_count'=>0
		);
		$result = $adminInfoDao->saveRecord($data);
		if ($result === false){
			throw new \Exception('修改失败');
		}
	}
	
	public function getAdminInfoWithMobile($mobile){
		$map = array(
			'mobile'=>$mobile,
		);
		return $this->adminInfoDao()->findRecord($map);
	}
	
	public function adminPasswordModify($params){
		if (empty($params['admin_id'])) throw new \Exception('系统错误');
		if (empty($params['password_old'])) throw new \Exception('原密码不能为空');
		if (empty($params['password'])) throw new \Exception('密码不能为空');
		if (empty($params['password2'])) throw new \Exception('密码不能为空');
		$adminInfoDao = $this->adminInfoDao();
		$admin = $adminInfoDao->getRecord($params['admin_id']);
		if (empty($admin)){
			throw new \Exception('记录不存在');
		}
		if($admin['password'] != $adminInfoDao->password($params['password_old'], $admin['salt'])){
			throw new \Exception('原密码错误');
		}
		$data = array(
			'admin_id'=>$params['admin_id'],
			'password'=>trim($params['password']),
		);
		$result = $adminInfoDao->saveRecord($data);
		if ($result === false){
			throw new \Exception('修改失败');
		}
	}
	
	public function adminChangePwdByMobile($mobile, $password, $sys_id){
		$map = array(
				'mobile'=>$mobile,
				'sys_id'=>$sys_id,
		);
		$admin = $this->adminInfoDao()->findRecord($map);
		$data = array(
				'admin_id'=>$admin['admin_id'],
				'password'=>$password,
				'error_count'=>0
		);
		return $this->adminInfoDao()->saveRecord($data);
	}
	
	public function deleteAdmin($id){
		$info = $this->getRecord($id);
		if(empty($info)) return;
		$map = array(
			'admin_id'=>$id,
		);
		$result = $this->adminInfoDao()->deleteAdmins($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function adminPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		isset($params['sys_id']) && $map['sys_id'] = intval($params['sys_id']);
		isset($params['org_id']) && $map['org_id'] = intval($params['org_id']);
		isset($params['is_admin']) && $map['is_admin'] = $params['is_admin'];
		isset($params['keyword']) && $map['admin_name|mobile'] = array('like', '%'.trim($params['keyword']).'%');
		isset($params['admin_type']) && $map['admin_type'] = $params['admin_type'];
		
		$adminInfoDao = $this->adminInfoDao();
		$count = $adminInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $adminInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function adminAllList($map, $orderby){
		$orderby = empty($orderby) ? 'add_time desc' : $orderby;
		$list = $this->adminInfoDao()->searchAllRecords($map, $orderby);
		return $this->outPutForList($list);
	}
	
	public function adminCount($map){
		return $this->adminInfoDao()->searchRecordsCount($map);
	}
	
	public function adminFieldList($admin_ids){
		return $this->adminInfoDao()->getRecords($admin_ids);
	}
	
	public function login($params){
		if (!isset($params['sys_id'])) throw new \Exception('系统错误');
		$adminInfoDao = $this->adminInfoDao();
		$map = array(
			'username|mobile'=>$params['username'],
			'sys_id'=>$params['sys_id'],
		);
		$admin = $adminInfoDao->findRecord($map);
		if(empty($admin)) throw new \Exception('用户不存在');
		if($admin['status'] != 0) throw new \Exception('登录异常，请联系管理员解决。');
		$error_count_max = 5;
		if($admin['error_count'] >= $error_count_max) throw new \Exception('密码错误超过'.$error_count_max.'次, 请先修改密码。');
		if ($adminInfoDao->password($params['password'], $admin['salt']) != $admin['password']){
			$adminInfoDao->errorCountIncrease($admin['admin_id']);
			$error_count = $admin['error_count'] + 1;
			throw new \Exception('密码错误（'.$error_count.'次）。');
		}
		
		//如果是分销商，判断是否关闭
		if ($admin['org_id'] > 1) {
			$distributor = $this->distributorInfoDao()->getRecord($admin['org_id']);
			if ($distributor['status'] == Status::DistributorStatusClose) {
				throw new \Exception('店铺已关闭');
			}
			if ($distributor['is_delete'] == 1) {
				throw new \Exception('店铺已删除');
			}
		}
		
		$result = $adminInfoDao->logined($admin['admin_id'], $params['password']);
		if ($result === false) throw new \Exception('登录失败');
		
		//登录日志
		$data = array(
				'admin_id'=>$admin['admin_id'],
				'admin_name'=>$admin['admin_name'],
				'log_info'=>'管理员登录',
				'sys_id'=>$params['sys_id'],
				'org_id'=>$admin['org_id']
		);
		$result = $this->adminLogDao()->addRecord($data);
		
		return $admin;
	}
	
	public function loginByPhone($params){
		$adminInfoDao = $this->adminInfoDao();
		$map = array(
			'mobile'=>$params['username'],
		);
		$admin = $adminInfoDao->findRecord($map);
		if(empty($admin)) throw new \Exception('用户不存在', 1003);
		if($admin['status'] != 0) throw new \Exception('登录异常，请联系管理员解决。', 1004);
		$error_count_max = 5;
		if($admin['error_count'] >= $error_count_max) throw new \Exception('密码错误超过'.$error_count_max.'次, 请先修改密码。', 1005);
		if ($adminInfoDao->password($params['password'], $admin['salt']) != $admin['password']){
			$adminInfoDao->errorCountIncrease($admin['admin_id']);
			$error_count = $admin['error_count'] + 1;
			throw new \Exception('密码错误（'.$error_count.'次）。', 1006);
		}
	
		//如果是分销商，判断是否关闭
		if ($admin['org_id'] > 1) {
			$distributor = $this->distributorInfoDao()->getRecord($admin['org_id']);
			if ($distributor['status'] == Status::DistributorStatusNone) {
				throw new \Exception('店铺未审核', 1007);
			}elseif ($distributor['is_delete'] == Status::DistributorStatusClose) {
				throw new \Exception('店铺已关闭', 1008);
			}
		}
	
		$result = $adminInfoDao->logined($admin['admin_id'], $params['password']);
		if ($result === false) throw new \Exception('登录失败', 1009);
	
		//登录日志
		$data = array(
			'admin_id'=>$admin['admin_id'],
			'admin_name'=>$admin['admin_name'],
			'log_info'=>'管理员登录',
			'sys_id'=>$admin['sys_id'],
			'org_id'=>$admin['org_id']
		);
		$result = $this->adminLogDao()->addRecord($data);
		return $admin;
	}
	
	public function logined($admin_id){
		return $this->adminInfoDao()->logined($admin_id);
	}
	
	public function getRole($id){
		return $this->adminRoleDao()->getRecord($id);
	}
	
	public function rolePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array(
			'sys_id'=>intval($params['sys_id']),
			'org_id'=>intval($params['org_id']),
		);
		
		$adminRoleDao = $this->adminRoleDao();
		$count = $adminRoleDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'role_id desc' : $params['orderby'];
			$list = $adminRoleDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
			$department_list = $this->adminDepartmentDao()->searchFieldRecords();
			foreach ($list as $k => $v) {
				$list[$k]['department_name'] = $department_list[$v['department_id']]['department_name'];
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function roleFieldList($map){
		$list = $this->adminRoleDao()->findRecords($map);
		
		if (!empty($list)) {
			$department_list = $this->adminDepartmentDao()->findRecords($map);
			foreach ($list as $k => $v) {
				$list[$k]['department_name'] = $department_list[$v['department_id']]['department_name'];
			}
		}
		
		return $list;
	}
	
	public function roleList($params){
		$map = array(
			'sys_id'=>intval($params['sys_id']),
			'org_id'=>intval($params['org_id']),
		);
		$list = $this->adminRoleDao()->findRecords($map);
		return $list;
	}
	
	public function roleCreateOrModify($params){
		if ($params['sys_id'] < 1 || $params['org_id'] < 1) throw new \Exception('缺少参数');
		$rules = array(
				array('role_name','require','角色名称不能为空',1),
				array('department_id','require','所属部门不能为空',1),
		);
		$data = array(
				'role_name'=>trim($params['role_name']),
				'role_describe'=>trim($params['role_describe']),
				'sys_id'=>intval($params['sys_id']),
				'org_id'=>intval($params['org_id']),
				'department_id'=>trim($params['department_id']), //所属部门
		);
		
		//给权限加一个登陆进后台后跳转的默认页面的权限
		if(array_search('index_index',$params['purview'])==false){
			$params['purview'][]='index_index_index';
		}
		
		//var_dump($params['purview']);die();
		$data['action_list'] = implode(',', $params['purview']);
		$data['oa_action_list'] = implode(',', $params['oa_purview']); //APP权限
		if($params['role_id'] > 0){
			$data['role_id'] = $params['role_id'];
			$data['sys_id'] = $params['sys_id'];
		} else {
			$data['sys_id'] = $params['sys_id'];
			$data['org_id'] = $params['org_id'];
		}
		$adminRoleDao = $this->adminRoleDao();
		if (!$adminRoleDao->validate($rules)->create($data)){
			 throw new \Exception($adminRoleDao->getError());
		}
		if ($params['role_id'] > 0){
			M()->startTrans();
			$result = $adminRoleDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			$result = $this->adminInfoDao()->updateRole($data['role_id'], $data['action_list'], $data['oa_action_list']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			M()->commit();
		} else {
			$result = $adminRoleDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function roleModify($role_id, $data){
		if ($role_id < 1) throw new \Exception('缺少参数');
		
		$role_info = $this->adminRoleDao()->getRecord($role_id);
		if (empty($role_info)) throw new \Exception('角色不存在');
		
		$data['role_id'] = $role_id;
		$result = $this->adminRoleDao()->saveRecord($data);
		if ($result === false) throw new \Exception('系统错误');
	}
	
	public function roleDelete($params){
		if ($params['role_id'] < 1) throw new \Exception('缺少参数');
		M()->startTrans();
		$result = $this->adminInfoDao()->clearRole($params['role_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		$result = $this->adminRoleDao()->deleteRole($params['role_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function oaRoleCreateOrModify($params){
		if ($params['sys_id'] < 1 || $params['org_id'] < 1) throw new \Exception('缺少参数');
		$rules = array(
			array('role_name','require','名称是必须的！'),
		);
		$data = array(
			'role_name'=>trim($params['role_name']),
			'role_describe'=>trim($params['role_describe']),
			'sys_id'=>intval($params['sys_id']),
			'org_id'=>intval($params['org_id']),
		);
	
		$data['action_list'] = ','.implode(',', $params['purview']).',';
		if($params['role_id'] > 0){
			$data['role_id'] = $params['role_id'];
			$data['sys_id'] = $params['sys_id'];
		} else {
			$data['sys_id'] = $params['sys_id'];
			$data['org_id'] = $params['org_id'];
		}
		$adminRoleDao = $this->adminRoleDao();
		if (!$adminRoleDao->validate($rules)->create($data)){
			throw new \Exception($adminRoleDao->getError());
		}
		if ($params['role_id'] > 0){
			M()->startTrans();
			$result = $adminRoleDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			$result = $this->adminInfoDao()->updateOaRole($data['role_id'], $data['action_list']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			M()->commit();
		} else {
			$result = $adminRoleDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function oaRoleDelete($params){
		if ($params['role_id'] < 1) throw new \Exception('缺少参数');
		$info = $this->adminRoleDao()->getRecord($params['role_id']);
		if (empty($info) || $info['sys_id'] != $params['sys_id'] 
				|| $info['org_id'] != $params['org_id']) {
					throw new CsException('记录不存在', 1005);
		}
		M()->startTrans();
		$result = $this->adminInfoDao()->clearOaRole($params['role_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		$result = $this->adminRoleDao()->deleteRole($params['role_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function log($logstr){
		$data = array(
			'admin_id'=>session('uid'),
			'admin_name'=>session('username'),
			'log_info'=>$logstr,
		);
		return $this->adminLogDao()->addRecord($data);
	}
	
	public function logPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array(
			'sys_id'=>$params['sys_id'],
			'org_id'=>$params['org_id'],
		);
		$adminLogDao = $this->adminLogDao();
		$count = $adminLogDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id desc' : $params['orderby'];
			$list = $adminLogDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outPutForList($list),
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function departmentCreateOrModify($params){
		if ($params['sys_id'] < 1 || $params['org_id'] < 1) throw new \Exception('缺少参数');
		$data = array(
			'department_name'=>trim($params['department_name']),
			'department_image'=>trim($params['department_image']),
		);
		$adminDepartmentDao = $this->adminDepartmentDao();
		if (!$adminDepartmentDao->create($data)){
			throw new \Exception($adminDepartmentDao->getError());
		}
		if ($params['department_id'] > 0){
			$data['department_id'] = $params['department_id'];
			$result = $adminDepartmentDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$data['sys_id'] = intval($params['sys_id']);
			$data['org_id'] = intval($params['org_id']);
			$result = $adminDepartmentDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function adminFindService($where = array(), $filed = array())
	{
		return $this->adminInfoDao()->findFieldRecord($where, $filed);
	}

	public function adminRoleFindService($where = array(), $filed = array())
	{
		return $this->adminRoleDao()->recordField($where, $filed);
	}


	public function departmentDelete($params){
		if ($params['department_id'] < 1) throw new \Exception('缺少参数');
		/* $map = [
			'department_id'=>$params['department_id'],
		]; */
		$map = array('department_id'=>$params['department_id']);
		$this->adminDepartmentDao()->where($map)->delete();
	}
	
	public function departmentList($map){
		return $this->adminDepartmentDao()->where($map)->select();
	}
	
	public function getDepartment($id){
		return $this->adminDepartmentDao()->getRecord($id);
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$admin = $this->adminInfoDao()->find($v['admin_id']);
				if ($admin['is_admin'] != 1) {
					$admin_role = $this->adminRoleDao()->find($admin['role_id']);
					$list[$k]['role_name'] = $admin_role['role_name'];
				}else {
					$list[$k]['role_name'] = '超级管理员';
				}
			}
		}
		
		return $list;
	}
	
	private function outPutForInfo($info) {
		if (!empty($info)) {
			//角色
			if ($info['is_admin'] != 1) {
				$admin_role = $this->adminRoleDao()->find($info['role_id']);
				$info['role_name'] = $admin_role['role_name'];
				//部门
				$department_info = $this->adminDepartmentDao()->getRecord($admin_role['department_id']);
				$info['department_name'] = $department_info['department_name'];
				//性别
				$info['sex_name'] = \Common\Basic\User::$sex[$info['sex']];
				//管理员类型
				$info['admin_type_name'] = Status::$adminTypeList[$info['admin_type']];
			}else {
				$info['role_name'] = '超级管理员';
			}
		}
		return $info;
	}
	
	//添加工资
	public function addAdminWage($data = array())
	{
		$preg_match = '/^[0-9]*(\.[0-9]{1,2})?$/';
		if (empty($data['admin_id'])) throw new \Exception('人员不存在');
		if (empty($data['month'])) throw new \Exception('请选择发放月份');
		if (empty($data['post_salary'])) throw new \Exception('请输入岗位工资');
		if (!preg_match($preg_match, $data['post_salary'])) throw new \Exception('请重新输入岗位工资');
		if (empty($data['legal_wage'])) throw new \Exception('请输入法定工资');
		if (!preg_match($preg_match, $data['legal_wage'])) throw new \Exception('请重新输入法定工资');
		$adminWageFind = $this->adminWageBao()->where(array('admin_id' => $data['admin_id'], 'month' => $data['month']))->find();
		if ($adminWageFind) throw new \Exception('工资已经发放，请在工资历史记录中查看');
		return $this->adminWageBao()->data($data)->add();
	}

	public function updateAdminWage($where = array(), $data = array())
	{
		$preg_match = '/^[0-9]*(\.[0-9]{1,2})?$/';
		if (empty($data['month'])) throw new \Exception('请选择发放月份');
		if (empty($data['post_salary'])) throw new \Exception('请输入岗位工资');
		if (!preg_match($preg_match, $data['post_salary'])) throw new \Exception('请重新输入岗位工资');
		if (empty($data['legal_wage'])) throw new \Exception('请输入法定工资');
		if (!preg_match($preg_match, $data['legal_wage'])) throw new \Exception('请重新输入法定工资');
		$adminWageFind = $this->adminWageBao()->where(array('admin_wage_id' => $where['admin_wage_id']))->find();
		if ($adminWageFind['status'] == 1) throw new \Exception('工资已经发放，请在工资历史记录中查看');
		return $this->adminWageBao()->where($where)->data($data)->save();
	}

	public function commissionList($params)
	{
		$map = array();
		$orderBy = array('admin_wage_id' => 'DESC');
		$map['admin_id'] = $params['admin_id'];
		$count = $this->adminWageBao()->where($map)->count();
		$data = $this->adminWageBao()->where($map)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
		$_list = array();
		if ($count) {
			foreach ($data as $key => $val) {
				$_t = $val;
				$_t['inputtime'] = date('Y-m-d H:i:s', $val['inputtime']);
				$_list[] = $_t;
			}
		}
		return array(
			'list' => $_list,
			'count' => $count,
			'pagesize' => $params['pagesize'],
		);
	}

	public function adminWageGrantService($where, $data){
		if (empty($where['admin_wage_id'])) throw new \Exception('发生致命错误');
		$wageFind = $this->adminWageBao()->where(array('admin_wage_id' => $where['admin_wage_id']))->find();
		if (empty($wageFind)) throw new \Exception('发生致命错误');
		return $this->adminWageBao()->where($where)->data($data)->save();
	}

	public function adminDepartmentService($map = array())
	{
		$where = array();
		if ($map['personnel_role']) {
			$where['department_id'] = array('in', $map['personnel_role']);
		}
		if($map['sys_id']){
			$where['sys_id'] = $map['sys_id'];
		}
		if($map['org_id']){
			$where['org_id'] = $map['org_id'];
		}
		return $this->adminDepartmentDao()->searchListRecords($where);
	}

	public function pcWageService($params)
	{
		$map = array();
		$orderBy = array('admin_wage_id' => 'DESC');
		$map['admin_id'] = $params['admin_id'];
		$count = $this->adminWageBao()->where($map)->count();
		$data = $this->adminWageBao()->where($map)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
		$_list = array();
		return array(
			'list' => $data,
			'count' => $count,
			'pagesize' => $params['pagesize'],
		);
	}


	public function appWageService($params)
	{
		$map = array();
		$orderBy = array('admin_wage_id' => 'DESC');
		if ($params['admin_id']) {
			$map['admin_id'] = $params['admin_id'];
		}
		if ($params['department_id']) {
			//查找角色
			$where = array('department_id'=>$params['department_id']);
			$roles = $this->adminRoleDao()->findRecords($where);
			if (empty($roles)) return array();
			//查找管理员
			$role_ids = array_keys($roles);
			$where = array('role_id'=>array('in', $role_ids));
			$admins = $this->adminInfoDao()->getFieldRecord($where);
			if (empty($admins)) return array();
			$admin_ids = array_keys($admins);
			
			$map['admin_id'] = array('in', $admin_ids);
		}
		$count = $this->adminWageBao()->where($map)->count();
		$data = $this->adminWageBao()->where($map)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
		$_list = array();
		$year = '';
		if ($count) {
			foreach ($data as $v) {
				$admin_ids[] = $v['admin_id'];
			}
			$admins = $this->adminInfoDao()->getRecords($admin_ids);
			$roles = $this->adminRoleDao()->findRecords();
			
			foreach ($data as $key => $val) {
				$_t = $val;
				$_t['inputtime'] = date('Y-m-d H:i:s', $val['inputtime']);
				$_t['month'] = date('m', strtotime($val['month']));
				if ($val['status'] == 1) {
					$_t['status_image'] = "icon53.png";
				} else {
					$_t['status_image'] = "icon52.png";
				}
				$year = date('Y', strtotime($val['month']));
				
				//管理员信息
				$_t['admin_name'] = $admins[$val['admin_id']]['admin_name'];
				$_t['avatar'] = $admins[$val['admin_id']]['avatar'];
				$_t['role_name'] = $roles[$admins[$val['admin_id']]['role_id']]['role_name'];
				$_list[date('Y', strtotime($val['month']))][] = $_t;
			}
		}
		return array(
			'year' => $year,
			'list' => $_list,
			'count' => $count,
			'pagesize' => $params['pagesize'],
		);
	}

	public function adminWageFind($admin_wage_id, $field = array())
	{
		return $this->adminWageBao()->where(array('admin_wage_id' => $admin_wage_id))->field($field)->find();
	}

	private function adminWageBao()
	{
		return D('AdminWage');
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
	
	private function adminLogDao(){
		return D('Common/Admin/AdminLog');
	}
	
	private function adminActionDao(){
		return D('Common/Admin/AdminAction');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function smsCodeDao(){
		return D('Common/Temp/SmsCode');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}