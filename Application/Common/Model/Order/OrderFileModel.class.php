<?php
namespace Common\Model\Order;
use Think\Model\RelationModel;

class OrderFileModel extends RelationModel {
	//tableName属性来改变默认的规则
    protected $tableName = 'order_file';
    protected $pk = 'file_id';
    
    protected $_validate = array(
    		array('order_id','require','订单号不能为空',1),
    		array('upload_path','require','文件不能为空',1),
    );
    
    protected $_auto = array (
    		//array('status','1'),  // 新增的时候把status字段设置为1
    		//array('password','md5',3,'function') , // 对password字段在新增和编辑的时候使md5函数处理
    		//array('name','getName',3,'callback'), // 对name字段在新增和编辑的时候回调getName方法
    		//array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对add_time字段在更新的时候写入当前时间戳
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
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    
    public function searchFieldRecords($map, $field = 'file_id, upload_path, file_name, file_size'){
    	return $this->where($map)->getField($field);
    }
}