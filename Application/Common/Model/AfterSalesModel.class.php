<?php
namespace Common\Model;
use Think\Model\RelationModel;

class AfterSalesModel extends RelationModel {
    protected $tableName = 'after_sales';
    protected $pk = 'id';
    
    protected $_validate = array(
    		array('type','require','类型不能为空'), //默认情况下用正则进行验证
    		array('type',array(1,2,3),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		array('reason','require','原因不能为空'), //默认情况下用正则进行验证
    		array('pictures','require','图片不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('shipping_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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
	
	public function delRecords($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}