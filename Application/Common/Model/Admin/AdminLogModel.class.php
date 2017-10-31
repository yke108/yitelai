<?php
namespace Common\Model\Admin;
use Think\Model\RelationModel;

class AdminLogModel extends RelationModel {
    protected $tableName = 'admin_log';
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		$data['log_time'] = time();
		$data['ip_address'] = get_client_ip();
		return $this->add($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}