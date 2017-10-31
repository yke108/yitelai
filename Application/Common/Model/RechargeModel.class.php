<?php
namespace Common\Model;
use Think\Model;

class RechargeModel extends Model{
	public function rechargeInfo($id){
		$id = intval($id);
		if($id < 1) return array();
		$info = $this->find($id);
		return $info;
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
	
	public function updateRecord($map, $data){
		$data['update_time'] = NOW_TIME;
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