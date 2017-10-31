<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;

class DesignerCaseModel extends RelationModel {
    protected $tableName = 'designer_case';
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
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    //定义查询方法
    public function allRecords($map,$orderby){
    	return $this->where($map)->order($orderby)->select();
    }
	
	//获取数据分组数据
	public function groupFieldRecord($map,$group='region_code',$field='region_code',$orderby='case_id desc'){
		
        return $this->where($map)->order($orderby)->group($group)->getField($field,true);
		
    }
    
    public function getFieldRecord($map,$field='case_id, case_name, picture'){
    	return $this->where($map)->getField($field);
    }
}