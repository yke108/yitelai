<?php
namespace Common\Model\Information\Community;
use Think\Model\RelationModel;

class CommentModel extends RelationModel {
    protected $trueTableName = 'info_community_topic_comment';
    
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
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}