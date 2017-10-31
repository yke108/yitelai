<?php
namespace Common\Model\Story;
use Think\Model\RelationModel;

class StoryInfoModel extends RelationModel {
    protected $tableName = 'story_info';
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
	
	public function like($story_id){
		return $this->where(array('story_id'=>$story_id))->setInc('good_num');
	}
	
	public function clap($story_id){
		return $this->where(array('story_id'=>$story_id))->setInc('bad_num');
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
    
    public function getFieldRecords($map, $field = 'story_id, user_id, cat_id, story_title, story_image'){
    	return $this->where($map)->getField($field);
    }
    
    public function increaseRewardNum($story_id, $reward_num) {
    	$map = array('story_id'=>$story_id);
    	return $this->where($map)->setInc('reward_num', $reward_num);
    }
}