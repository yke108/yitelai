<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class GoodsModel extends RelationModel {
    protected $tableName = 'distributor_goods';
    protected $pk = 'record_id';
    
    protected $_validate = array(
    		
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->alias('a')->field('b.*, a.*')
        		->join('JOIN __GOODS__ b ON b.goods_id=a.goods_id')
        		->join('LEFT JOIN __DISTRIBUTOR_INFO__ c ON c.distributor_id=a.distributor_id')
	//			->join('LEFT JOIN __GOODS_PRODUCT__ d ON c.product_id=d.product_id')
   				->find($id);
	}
	
	public function findRecord($map){
		return $this->alias('a')->field('b.*, a.*')
        		->join('JOIN __GOODS__ b ON b.goods_id=a.goods_id')
        		->join('LEFT JOIN __DISTRIBUTOR_INFO__ c ON c.distributor_id=a.distributor_id')
        		//->join('LEFT JOIN __GOODS_SPEC_VALUES__ d ON d.goods_id=a.goods_id')
				->where($map)->find();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit, $field = 'b.*, a.*'){
        $list = $this->alias('a')->field($field)
        		->join('JOIN __GOODS__ b ON b.goods_id=a.goods_id')
        		->join('LEFT JOIN __DISTRIBUTOR_INFO__ c ON c.distributor_id=a.distributor_id')
        		//->join('LEFT JOIN __GOODS_SPEC_VALUES__ d ON d.goods_id=a.goods_id')
        		->where($map)->order($orderBy)->page($start, $limit)->select();
        return $list;
    }
	
	public function searchRecordsCount($map){
		return $this->alias('a')->field('b.*, a.*')
        		->join('JOIN __GOODS__ b ON b.goods_id=a.goods_id')
        		->join('LEFT JOIN __DISTRIBUTOR_INFO__ c ON c.distributor_id=a.distributor_id')
        		//->join('LEFT JOIN __GOODS_SPEC_VALUES__ d ON d.goods_id=a.goods_id')
				->where($map)->count();
    }
    
    public function searchAllRecords($map){
    	$list = $this->alias('a')->field('b.*, a.*')
		    	->join('JOIN __GOODS__ b ON b.goods_id=a.goods_id')
		    	->join('LEFT JOIN __DISTRIBUTOR_INFO__ c ON c.distributor_id=a.distributor_id')
		    	//->join('LEFT JOIN __GOODS_SPEC_VALUES__ d ON d.goods_id=a.goods_id')
		    	->where($map)->select();
    	return $list;
    }
}