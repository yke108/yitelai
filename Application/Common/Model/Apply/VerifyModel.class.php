<?php
namespace Common\Model\Apply;
use Think\Model;

class VerifyModel extends Model {
    protected $tableName = 'apply_verify';
	
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
	
	public function listOfApply($apply_id){
		$map = [
			'apply_id'=>$apply_id,
		];
		return $this->where($map)->select();
	}
}