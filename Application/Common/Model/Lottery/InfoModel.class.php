<?php
namespace Common\Model\Lottery;
use Think\Model;

class InfoModel extends Model{
	protected $tableName = 'lottery_info';
	
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
    
    public function getRecords($ids){
    	if (!is_array($ids) || count($ids) < 1) return array();
    	$map = array(
    		'lottery_id'=>array('in', $ids),
    	);
    	return $this->where($map)->getField('lottery_id, lottery_name, is_open');
    }
    
    public function recordsList($map){
    	return $this->where($map)->getField('lottery_id, lottery_name, is_open');
    }
    
	public function getRecordForUser(){
		$map = array(
			'is_open'=>1,
			'start_time'=>array('elt', NOW_TIME),
		);
		return $this->order('lottery_id desc')->findRecord($map);
	}
}