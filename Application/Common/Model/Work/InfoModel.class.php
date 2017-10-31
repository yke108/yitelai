<?php
namespace Common\Model\Work;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
    protected $tableName = 'work_target';
	
	public function getFieldRecord($map,$field)
	{ 
		return $this->where($map)->getField($field);
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
	
	public function searchRecordsCount($map)
	{ 
		return $this->where($map)->count();
	}
	
	public function searchRecords($map,$order,$start,$end){
        return $this->where($map)->order($order)->page($start,$end)->select();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function getRecordorderby($order,$map){
		return $this->where($map)->order($order)->select();
	}
	
	public function searchRecordslist($map,$order){
		return $this->where($map)->order($order)->select();
	}
}