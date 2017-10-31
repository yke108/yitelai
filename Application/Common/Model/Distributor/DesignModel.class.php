<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class DesignModel extends RelationModel {
    protected $tableName = 'distributor_design';
    
    protected $_validate = array(
    		array('area','require','装修区域不能为空',1),
    		array('manager','require','负责人不能为空',1),
    		array('shopkeeper','require','店长不能为空',1),
    		array('designer_id','require','设计师不能为空',1),
    		array('description','require','详细说明不能为空',1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchRecordsField($map){
    	return $this->where($map)->getField('activity_id, title, picture');
    }
    
    public function getRecordsField($ids){
    	$map['activity_id'] = array('in', $ids);
    	return $this->where($map)->getField('activity_id, title, picture');
    }
}