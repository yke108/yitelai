<?php
namespace Common\Model\Sms;
use Think\Model;

class SmsLogModel extends Model{
	protected $tableName = 'sms_log';
	
	public function getRecord($id){
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
}