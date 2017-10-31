<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class ActivityModel extends RelationModel {
    protected $tableName = 'distributor_activity';
    
    protected $_validate = array(
    		array('title','require','活动主题不能为空',1),
    		array('start_time','require','开始时间不能为空',1),
    		array('end_time','require','结束时间不能为空',1),
    		array('cost','require','活动费用不能为空',1),
    		array('target','require','销售目标不能为空',1),
    		array('description1','require','优惠活动一不能为空',1),
    		array('description2','require','优惠活动二不能为空',1),
    		array('cat_ids','require','主推系列不能为空',1),
    		array('record_ids','require','主推产品不能为空',1),
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