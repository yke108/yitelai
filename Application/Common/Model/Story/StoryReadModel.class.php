<?php
namespace Common\Model\Story;
use Think\Model\RelationModel;

class StoryReadModel extends RelationModel {
    protected $tableName = 'story_read';
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function searchRecord($map, $orderBy){
		return $this->where($map)->order($orderBy)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function updateRecord($map, $data){
		return $this->where($map)->save($data);
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->select();
    }
    
    public function searchFieldRecords($map, $field = ''){
    	return $this->where($map)->getField($field);
    }
}