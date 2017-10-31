<?php
namespace Common\Model\Help;
use Think\Model\RelationModel;

class HelpCatModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'help_cat';
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

    public function allRecords(){
    	return $this->where($map)->select();
    }
}