<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;

class DesignerMessageModel extends RelationModel {
    protected $tableName = 'designer_message';
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map,$orderby){
		return $this->where($map)->order($orderby)->find();
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