<?php
namespace Common\Model\View;
use Think\Model\RelationModel;

class ViewModel extends RelationModel {
    protected $tableName = 'pageview';
	
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
}