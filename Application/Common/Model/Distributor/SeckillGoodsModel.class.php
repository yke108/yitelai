<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class SeckillGoodsModel extends RelationModel {
    protected $tableName = 'seckill_distributor_goods';
    protected $pk = 'seckill_id';
    
    protected $_validate = array(
    		array('distributor_id','require','分销商ID不能为空'), //默认情况下用正则进行验证
    		array('record_id','require','商品ID不能为空'), //默认情况下用正则进行验证
    		//array('distributor_product_id','require','货品ID不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __DISTRIBUTOR_GOODS_PRODUCT__ b ON b.record_id=a.record_id')
   				->find($id);
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
        return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.record_id')
        		->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchRecord($map, $orderBy){
    	return $this->alias('a')->field('b.*, a.*')
		    	->join('LEFT JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.record_id')
		    	->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->alias('a')
		    	->join('LEFT JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.record_id')
				->where($map)->count();
    }
    
    public function searchAllRecords($map){
    	return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.record_id')
    			->where($map)->select();
    }
}