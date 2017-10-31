<?php
namespace Common\Model\Lottery;
use Think\Model;

class LogModel extends Model{
	protected $tableName = 'lottery_log';
	
	public function getRecord($id){
		if($id < 1) return array();
		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}

	public function addRecord($data){
		$data['add_time'] = NOW_TIME;
		return $this->add($data);
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
}