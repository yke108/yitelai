<?php
namespace Common\Model\User;
use Think\Model\RelationModel;

class UserApplyModel extends RelationModel {
    protected $tableName = 'user_apply';
	
	public function getFieldRecord($map,$field='apply_id,name',$bool=null){
		return $this->where($map)->getField($field,$bool);
	}
	
	public function getRecord($id){
   		return $this->find($id);
	}
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data,$map){
		return $this->where($map)->save($data);
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