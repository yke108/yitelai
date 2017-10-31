<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;

class DesignerMessageTypeModel extends RelationModel {
    protected $tableName = 'designer_messagetype';
    
    protected $_validate = array(
    		array('type_name','require','名称不能为空'), //默认情况下用正则进行验证
    		array('type_name','','名称已存在',0,'unique',2), // 在新增的时候验证name字段是否唯一
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map,$orderby){
		return $this->where($map)->order($orderby)->find();
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
    
    public function searchFieldRecords($map, $field = 'type_id, type_name, sort_order'){
    	return $this->where($map)->getField($field);
    }
}