<?php
namespace Common\Model\System;
use Think\Model\RelationModel;

class ActionModel extends RelationModel {
    protected $tableName = 'system_action';
	public function getRecord($id){
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
	
	public function getActions($map){
		return $this->where($map)->order('parent_id asc')
		->getField('action_id, action_name, action_code, parent_id');
	}
}