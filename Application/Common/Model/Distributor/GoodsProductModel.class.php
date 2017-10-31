<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class GoodsProductModel extends RelationModel {
    protected $tableName = 'distributor_goods_product';
    protected $pk = 'product_record_id';
    
    protected $_validate = array(
    		array('record_id','require','分销商商品ID不能为空'), //默认情况下用正则进行验证
    		array('product_price','require','货品价格不能为空'), //默认情况下用正则进行验证
    		array('market_price','require','市场参考价不能为空'), //默认情况下用正则进行验证
    		array('stock_num','require','库存数量不能为空'), //默认情况下用正则进行验证
    		array('notify_num','require','预警数量不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
	
	public function getFieldRecord($map,$field){
		return $this->where($map)->getField($field);
	}
    
	public function getRecord($id){
   		return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
   				->find($id);
	}
	
	public function findRecord($map){
		return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
				->where($map)->find();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->alias('a')->field('b.*, a.*')
        		->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
        		->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchDistributorRecords($map, $orderBy, $start, $limit){
    	return $this->alias('a')->field('b.*, a.*')
		    	->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
		    	->join('LEFT JOIN __DISTRIBUTOR_GOODS__ c ON c.record_id=a.record_id')
		    	->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function searchDistributorRecordsCount($map){
    	return $this->alias('a')
		    	->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
		    	->join('LEFT JOIN __DISTRIBUTOR_GOODS__ c ON c.record_id=a.record_id')
    			->where($map)->count();
    }
    
    public function searchRecord($map, $orderBy){
    	return $this->alias('a')->field('b.*, a.*, b.stock_price as platform_stock_price, b.market_price as platform_market_price')
		    	->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
		    	->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->alias('a')->field('b.*, a.*, b.stock_price as platform_stock_price, b.market_price as platform_market_price')
        		->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
    			->where($map)->order($orderBy)->select();
    }
	
	//查询进货价
	public function getStockPriceAmount($map){
		$amount=$this->alias('a')
        		->join('LEFT JOIN __GOODS_PRODUCT__ b ON b.product_id=a.product_id')
    			->join('LEFT JOIN __ORDER_GOODS__ og ON og.product_id=a.id')
				->where($map)->sum("og.goods_number*b.stock_price");
		return $amount;
	}
}