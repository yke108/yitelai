<?php
namespace Common\Model\Admin;
use Think\Model;

class AdminSessionModel extends Model{
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		$data['expiry'] = NOW_TIME + 604800;
		return $this->add($data);
	}
	
	public function clearExpiryRecords($admin_id = 0){
		$map = array(
			'expiry'=>array('lt', NOW_TIME),
			'_logic'=>'OR',
		);
		$admin_id && $map['admin_id'] = $admin_id;
		return $this->where($map)->delete();
	}
}