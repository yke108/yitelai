<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;

class designerChargeModel extends RelationModel {
    protected $tableName = 'designer_charge';
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function getFieldRecord($map,$field='charge_id,charge_name',$page,$pagesize){
		return $this->where($map)->page($page,$pagesize)->getField($field);
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