<?php
namespace Common\Model\Order;
use Think\Model\RelationModel;

class OrderDetailLogModel extends RelationModel {
	//tableName属性来改变默认的规则
    protected $tableName = 'order_detail_log';
    protected $pk = 'log_id';
    
    protected $_validate = array(
    		array('detail_id','require','明细ID不能为空',1),
    		array('admin_id','require','管理员ID不能为空',1),
    		array('content','require','日志内容不能为空',1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    );
    
    public function saveRecord($data){
    	return $this->save($data);
    }
    
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map, $orderBy = 'log_id desc'){
    	return $this->where($map)->order($orderBy)->select();
    }
    
    public function searchFieldRecords($map, $field = 'log_id, detail_id, admin_id, content, add_time'){
    	return $this->where($map)->getField($field);
    }
}