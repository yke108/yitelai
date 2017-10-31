<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class FineTypeModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'distributor_fine_type';
    protected $pk = 'fine_id';
    
    protected $_validate = array(
    		array('type_name','require','类型名称不能为空',1),
    );
    
    protected $_auto = array (
    		//array('add_time','time',1,'function'), // 对add_time字段在插入的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($ids){
		return $this->where(array('fine_id'=>array('in',$ids)))->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
    
    public function searchFieldRecords($map, $field = 'type_id, type_name'){
    	return $this->where($map)->getField($field);
    }
}