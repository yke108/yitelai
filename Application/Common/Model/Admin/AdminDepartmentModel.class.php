<?php
namespace Common\Model\Admin;
use Think\Model\RelationModel;

class AdminDepartmentModel extends RelationModel {
    protected $tableName = 'admin_department';
    
    protected $_validate = array(
    		array('department_name','require','部门不能为空',1),
    		array('sys_id,org_id,department_name','','部门名称已存在',0,'unique',3),
    );
    
    protected $_auto = array (
    		//array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}

	public function searchListRecords($map){
		return $this->where($map)->select();
	}

	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchFieldRecords($map, $field = 'department_id, department_name, department_image'){
    	return $this->where($map)->getField($field);
    }
    
    public function findRecords($map){
    	return $this->where($map)->getField('department_id, department_name, department_image');
    }
}