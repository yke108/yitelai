<?php
namespace Common\Model\Question;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
    protected $tableName = 'question_info';
	public function getRecord($id){
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
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function updateRecord($map, $data){
		return $this->where($map)->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchRecord($map, $orderBy){
    	return $this->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function allRecords($map){
    	return $this->where($map)->select();
    }
    
    public function getFieldRecords($map, $field = 'question_id, user_id, cat_id, title'){
    	return $this->where($map)->getField($field);
    }
}