<?php
namespace Common\Model\Activity;
use Think\Model\RelationModel;

class TeamBuyingModel extends RelationModel {
    protected $tableName = 'team_buying';
	
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function getFieldRecord($map,$field='act_id,act_name',$bool=null){
		return $this->where($map)->getField($field,$bool);
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
    public function allRecords($map,$orderby){
    	return $this->where($map)->order($orderby)->select();
    }
}