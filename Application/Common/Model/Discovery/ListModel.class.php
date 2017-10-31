<?php
namespace Common\Model\Discovery;
use Think\Model;

class ListModel extends Model{
	protected $tableName = 'discovery_list';
	
	public function getRecord($id){
		if($id < 1) return array();
		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}

	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}