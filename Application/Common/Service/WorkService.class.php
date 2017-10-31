<?php
namespace Common\Service;
use Common\Basic\Status;

class WorkService{
	// 添加
	public function add($data)
	{ 
		return $this->WorkInfoDao()->addRecord($data);
	}
	// 修改
	public function edit($data)
	{ 
		return $this->WorkInfoDao()->saveRecord($data);
	}
	// 删除
	public function del($id)
	{ 
		return $this->WorkInfoDao()->deleteRecord(array('id' => $id));
	}
	// 列表
	public function getlist($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['keyword'])
		{ 
			$where.= ' and (fullname = "'.$params['keyword'].'" or mobile ="'.$params['keyword'].'")';
		}
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		
		$count = $this->WorkInfoDao()->searchRecordsCount($where);
		$list = $this->WorkInfoDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);
		
		return array(
			'list' => $list,
			'count' => $count,
		);
	}
	// 详情
	public function getworkbyid($id)
	{ 
		$info = $this->WorkInfoDao()->getRecord($id);
		$info['check_status_label'] = $this->checkStatusLabel($info['check_status']);
		return $info;
	}
	// 前端-我的工作目标
	public function mywork($admin_id,$type,$id='')
	{
		if($id)
		{
			$map = array('admin_id' => $admin_id , 'type' => $type , 'id' => $id);
		}
		else
		{
			$map = array('admin_id' => $admin_id , 'type' => $type);
		}
		return $this->WorkInfoDao()->findRecord($map);
	}
	public function myCurrentwork($admin_id,$type)
	{
		switch ($type) {
			case 1:
				$map = array(
						'admin_id' => $admin_id,
						'type' => $type,
						'_string' => 'TO_DAYS(NOW()) = TO_DAYS(time)',
				);
			break;
			case 2:
				$map = array(
						'admin_id' => $admin_id,
						'type' => $type,
						'_string' => 'DAYOFWEEK(NOW()) = DAYOFWEEK(time)',
				);
			break;
			case 3:
				$map = array(
						'admin_id' => $admin_id,
						'type' => $type,
						'_string' => 'DAYOFMONTH(NOW()) = DAYOFMONTH(time)',
				);
			break;
			case 4:
				$map = array(
						'admin_id' => $admin_id,
						'type' => $type,
						'_string' => 'TIMESTAMPDIFF(YEAR,time,NOW()) = 0',
				);
			break;
		}
		return $this->WorkInfoDao()->findRecord($map);
	}
	// 前端-修改我的工作目标
	public function savemywork($data)
	{
		return $this->WorkInfoDao()->saveRecord($data);
	}
	// 前端-按条件查看工作目标
	public function getworklist($map=1,$order='id desc')
	{
		$list = $this->WorkInfoDao()->searchRecordslist($map,$order);
		return $this->outPutForList($list);
	}
	
	public function getPagerList($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['type']) {
			$map['type'] = $params['type'];
		}
		if ($params['admin_id']) {
			$map['admin_id'] = $params['admin_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if ($params['finish_status']) {
			$map['finish_status'] = $params['finish_status'];
		}
		if ($params['department_id']) {
			//角色
			$where = array('department_id'=>$params['department_id']);
			$role_list = $this->adminRoleDao()->findRecords($where);
			if (empty($role_list)) return array();
			//管理员
			$role_ids = array_keys($role_list);
			$where = array('role_id'=>array('in', $role_ids));
			$admin_list = $this->adminInfoDao()->getFieldRecord($where);
			if (empty($admin_list)) return array();
			$admin_ids = array_keys($admin_list);
			$map['admin_id'] = array('in', $admin_ids);
		}
		if ($params['time']) {
			switch ($params['type']) {
				case 1:
					$map['_string'] = 'TO_DAYS(NOW()) = TO_DAYS(time)';
					break;
				case 2:
					$map['_string'] = 'DAYOFWEEK(NOW()) = DAYOFWEEK(time)';
					break;
				case 3:
					$map['_string'] = 'DAYOFMONTH(NOW()) = DAYOFMONTH(time)';
					break;
				case 4:
					$map['_string'] = 'TIMESTAMPDIFF(YEAR,time,NOW()) = 0';
					break;
				default:
					$where['_string'] = 'TO_DAYS(NOW()) = TO_DAYS(time) OR DAYOFWEEK(NOW()) = DAYOFWEEK(time) OR DAYOFMONTH(NOW()) = DAYOFMONTH(time) OR TIMESTAMPDIFF(YEAR,time,NOW()) = 0';
					break;
			}
		}
		
		$count = $this->WorkInfoDao()->searchRecordsCount($map);
		$list = array();
		if ($count > 0) {
			$orderby = empty($params['orderby']) ? 'id desc' : $params['orderby'];
			$list = $this->WorkInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$this->outPutForList($list),
				'count'=>$count,
		);
	}
	
	private function checkStatusLabel($check_status) {
		$status_label = array(
				'0'=>'未审核',
				'1'=>'审核',
				'2'=>'审核不通过',
				'3'=>'审核通过',
		);
		return $status_label[$check_status];
	}
	
	public function get_finish_status() {
		$finish_status = array(
				'0'=>'未完成',
				'1'=>'已完成',
		);
		return $finish_status;
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			$distributor_ids = $admin_ids = array();
			foreach ($list as $v) {
				if ($v['distributor_id']) {
					$distributor_ids[] = $v['distributor_id'];
				}
				$admin_ids[] = $v['admin_id'];
			}
			if ($distributor_ids) {
				$distributors = $this->distributorInfoDao()->getFieldRecord(array('distributor_id'=>array('in', $distributor_ids)), 'distributor_id, distributor_name, region_code');
			}
			$admins = $this->adminInfoDao()->getRecords($admin_ids);
			$roles = $this->adminRoleDao()->findRecords();
			
			foreach ($list as $k => $v) {
				$list[$k]['finish_status_label'] = Status::$workTargetFinishStatusList[$v['finish_status']];
				//店铺
				if ($v['distributor_id']) {
					$list[$k]['distributor_name'] = $distributors[$v['distributor_id']]['distributor_name'];
					$list[$k]['region_name'] = $this->regionDao()->getRegionName($distributors[$v['distributor_id']]['region_code']);
				}
				//管理员
				$list[$k]['admin_name'] = $admins[$v['admin_id']]['admin_name'];
				$list[$k]['avatar'] = $admins[$v['admin_id']]['avatar'] ? picurl($admins[$v['admin_id']]['avatar'], 'b90') : '';
				//角色
				$list[$k]['role_id'] = $admins[$v['admin_id']]['role_id'];
				$list[$k]['role_name'] = $roles[$admins[$v['admin_id']]['role_id']]['role_name'];
			}
		}
		return $list;
	}
	
	private function outPutForInfo($info) {
		if (!empty($info)) {
			
		}
		return $info;
	}
	
	private function WorkInfoDao()
	{
		return D('Common/Work/Info');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function regionDao(){
		return D('Region');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function adminRoleDao(){
		return D('Common/Admin/AdminRole');
	}
}