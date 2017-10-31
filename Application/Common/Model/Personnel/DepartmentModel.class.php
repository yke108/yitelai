<?php
namespace Common\Model\Personnel;
use Think\Model\RelationModel;

class DepartmentModel extends RelationModel {
    protected $tableName = 'personnel_department';
	
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
	
	public function searchRecords($map=1){
        return $this->where($map)->select();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
}