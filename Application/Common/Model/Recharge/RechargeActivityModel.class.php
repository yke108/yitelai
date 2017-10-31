<?php
namespace Common\Model\Recharge;
use Think\Model;

class RechargeActivityModel extends Model{
	public function getFieldRecord($map,$field){
		return $this->where($map)->getField($field);
	}
	
	public function getFindRecord($id){
		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function updateRecord($data,$map){
		return $this->where($map)->save($data);
	}
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
    public function searchRecord($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
					
	public function searchRecordCount($map){
		return $this->alias('a')->where($map)->count();
    }

}