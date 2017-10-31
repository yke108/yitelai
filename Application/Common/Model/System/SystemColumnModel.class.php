<?php
namespace Common\Model\System;
use Think\Model\RelationModel;

class SystemColumnModel extends RelationModel {
    protected $tableName = 'system_column';
	public function getRecord($id){
		if ($id < 1) return array();
   		return $this->find($id);
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
    
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
}