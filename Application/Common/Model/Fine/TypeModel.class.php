<?php
namespace Common\Model\Fine;
use Think\Model\RelationModel;

class TypeModel extends RelationModel {
    protected $tableName = 'fine_type';
	
	public function getFieldRecord($map,$field)
	{ 
		return $this->where($map)->getField($field);
	}
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data,$map){
		return $this->where($map)->save($data);
	}
	
	public function searchRecords(){
        return $this->select();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
}