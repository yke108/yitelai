<?php
namespace Common\Model\Order;
use Think\Model\RelationModel;

class OrderDetailModel extends RelationModel {
	//tableName属性来改变默认的规则
    protected $tableName = 'order_detail';
    protected $pk = 'detail_id';
    
    protected $_validate = array(
    		array('order_id','require','订单号不能为空',1),
    		array('goods_name','require','商品名称不能为空',1),
    		array('goods_number','require','数量不能为空',1),
    		array('goods_price','require','价格不能为空',1),
    );
    
    protected $_auto = array (
    		//array('status','1'),  // 新增的时候把status字段设置为1
    		//array('password','md5',3,'function') , // 对password字段在新增和编辑的时候使md5函数处理
    		//array('name','getName',3,'callback'), // 对name字段在新增和编辑的时候回调getName方法
    		//array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对add_time字段在更新的时候写入当前时间戳
    );
    
    public function getRecord($id){
    	return $this->find($id);
    }
    
    public function findRecord($map){
    	return $this->where($map)->find();
    }
    
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
    
    public function searchFieldRecords($map, $field = 'detail_id, goods_name, goods_number, goods_price'){
    	return $this->where($map)->getField($field);
    }
}