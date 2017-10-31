<?php
namespace Common\Service;
use Common\Basic\Status;

class FineService{
	// 类型列表
	public function gettype()
	{ 
		return $this->FineTypeDao()->searchRecords();
	}
	// 单个类型
	public function gettypebyid($id)
	{ 
		return $this->FineTypeDao()->getRecord($id);
	}
	// 添加类型
	public function addtype($data)
	{ 
		return $this->FineTypeDao()->addRecord($data);
	}
	// 修改类型
	public function edittype($data)
	{ 
		return $this->FineTypeDao()->saveRecord($data);
	}
	// 删除类型
	public function deltype($id)
	{ 
		return $this->FineTypeDao()->deleteRecord(array('id' => $id));
	}
	// 列表
	public function getlist($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['fine_type'])
		{ 
			$where.= ' and fine_type = '.$params['fine_type'];
		}
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		
		
		$count = $this->FineListDao()->searchRecordsCount($where);
		$list = $this->FineListDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);
		if($list)
		{ 
			foreach($list as $k => $v)
			{ 
				 $list[$k]['fine_name'] = $this->FineTypeDao()->getFieldRecord(array('id' => $v['fine_type']),'fine_name');
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
		return $this->FineListDao()->getRecord($id);	
	}
	// 添加列表
	public function addlists($data)
	{ 
		return $this->FineListDao()->addRecord($data);
	}
	// 修改列表
	public function editlists($data)
	{ 
		return $this->FineListDao()->saveRecord($data);
	}
	private function FineTypeDao()
	{
		return D('Common/Fine/Type');
	}
	private function FineListDao()
	{ 
		return D('Common/Fine/List');
	}
}