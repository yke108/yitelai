<?php
namespace Common\Model\Story;
use Think\Model\RelationModel;

class StoryCatModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'story_cat';
	
	public function findFieldRecord($map,$field,$bool=null){
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
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function allRecords($map){
    	return $this->where($map)->select();
    }
    
    public function getFieldRecords($map, $field = 'cat_id, cat_name, picture'){
    	return $this->where($map)->getField($field);
    }
}