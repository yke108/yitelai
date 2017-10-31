<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;
//订单状态
class DesignerOrderStatusModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'designer_order_status';
	
	public function getFieldRecord($map,$field){
		return $this->where($map)->getField($field);
	}
	
	public function getStatusRecord($limit){
		return $this->order('sort_order asc')->page($limit,1)->find();
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
    public function allRecords($map,$orderby){
    	return $this->where($map)->order($orderby)->select();
    }
}