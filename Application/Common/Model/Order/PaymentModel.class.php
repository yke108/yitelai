<?php
namespace Common\Model\Order;
use Think\Model\RelationModel;

class PaymentModel extends RelationModel {
	//tableName属性来改变默认的规则
    protected $tableName = 'payment';
    protected $pk = 'payment_id';
    
    public function getRecord($id){
    	return $this->find($id);
    }
    
    public function findRecord($map){
    	return $this->where($map)->find();
    }
    
    public function saveRecord($data){
    	return $this->save($data);
    }
    
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
}