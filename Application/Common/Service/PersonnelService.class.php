<?php
namespace Common\Service;
use Common\Basic\Status;

class PersonnelService{
	// 组别列表
	public function getgroup()
	{ 
		return $this->PersonnelGroupDao()->searchRecords();
	}
	// 单个组别
	public function getgroupbyid($id)
	{ 
		return $this->PersonnelGroupDao()->getRecord($id);
	}
	// 添加组别
	public function addgroup($data)
	{ 
		return $this->PersonnelGroupDao()->addRecord($data);
	}
	// 修改组别
	public function editgroup($data)
	{ 
		return $this->PersonnelGroupDao()->saveRecord($data);
	}
	// 删除组别
	public function delgroup($id)
	{ 
		return $this->PersonnelGroupDao()->deleteRecord(array('id' => $id));
	}
	// 岗位列表
	public function getdepartment()
	{ 
		$result = $this->PersonnelDepartmentDao()->searchRecords();
		if($result)
		{ 
			foreach($result as $k => $v)
			{ 
				$result[$k]['group_name'] = $this->PersonnelGroupDao()->getFieldRecord(array('id' => $v['group_id']),'group_name');
			}
		}
		return $result;
	}
	// 单个岗位
	public function getdepartmentbyid($id)
	{ 
		return $this->PersonnelDepartmentDao()->getRecord($id);	
	}
	// 添加岗位
	public function adddepartment($data)
	{ 
		return $this->PersonnelDepartmentDao()->addRecord($data);
	}
	// 修改岗位
	public function editdepartment($data)
	{ 
		return $this->PersonnelDepartmentDao()->saveRecord($data);
	}
	// 删除岗位
	public function deldepartment($id)
	{ 
		return $this->PersonnelDepartmentDao()->deleteRecord(array('id' => $id));
	}
	// 列表
	public function getlist($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['keyword'])
		{ 
			$where.= ' and (fullname = "'.$params['keyword'].'" or mobile = "'.$params['keyword'].'")';
		}
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		if($params['group_id'])
		{ 
			$where.= ' and group_id = '.$params['group_id'];
		}
		if($params['department_id'])
		{ 
			$where.= ' and department_id = '.$params['department_id'];
		}
		
		
		$count = $this->PersonnelListDao()->searchRecordsCount($where);
		$list = $this->PersonnelListDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);
		if($list)
		{ 
			foreach($list as $k => $v)
			{ 
				$list[$k]['group_name'] = $this->PersonnelGroupDao()->getFieldRecord(array('id' => $v['group_id']),'group_name');
				$list[$k]['department'] = $this->PersonnelDepartmentDao()->getFieldRecord(array('id' => $v['department_id']),'department');
			}
		}
		
		return array(
			'list' => $list,
			'count' => $count,
		);
	}
	// 单个列表
	public function getlistsbyid($id)
	{ 
		return $this->PersonnelListDao()->getRecord($id);	
	}
	// 添加列表
	public function addlists($data)
	{ 
		return $this->PersonnelListDao()->addRecord($data);
	}
	// 修改列表
	public function editlists($data)
	{ 
		return $this->PersonnelListDao()->saveRecord($data);
	}
	// 删除列表
	public function dellists($id)
	{ 
		return $this->PersonnelListDao()->deleteRecord(array('id' => $id));
	}
	// 根据组别获取岗位
	public function getdepartmentbygroud($group_id)
	{ 
		return $this->PersonnelDepartmentDao()->searchRecords(array('group_id' => $group_id));
	}
	
	private function PersonnelGroupDao()
	{
		return D('Common/Personnel/Group');
	}
	private function PersonnelDepartmentDao()
	{ 
		return D('Common/Personnel/Department');
	}
	private function PersonnelListDao()
	{ 
		return D('Common/Personnel/List');
	}
}