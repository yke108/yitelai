<?php
namespace Common\Model\Admin;
use Think\Model\RelationModel;

class AdminRoleModel extends RelationModel {
    protected $tableName = 'admin_role';
	public function getRecord($id){
   		return $this->find($id);
	}
	public function recordField($where = array(), $filed = array()){
		return $this->where($where)->field($filed)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }

	public function searchFieldRecords($map, $field){
		return $this->where($map)->field($field)->select();
	}

	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function findRecords($map){
    	return $this->where($map)->getField('role_id, role_name, department_id, sys_id, org_id');
    }
    
    public function deleteRole($id) {
    	$this->delete($id);
    }
}