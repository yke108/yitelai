<?php
namespace Common\Model\Game;
use Think\Model;

class HitMouseModel extends Model{
	
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
	
	public function updateRecord($map, $data){
		$data['update_time'] = NOW_TIME;
		return $this->where($map)->save($data);
	}
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	public function delRecord($id){
		return $this->delete($id);
	}
	
    public function searchRecord($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordCount($map){
		return $this->where($map)->count();
    }

}