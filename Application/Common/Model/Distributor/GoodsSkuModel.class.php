<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class GoodsSkuModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'distributor_goods_sku';
    protected $pk = 'sku_id';
    
    protected $_validate = array(
    		array('record_id','require','商品ID不能为空'), //默认情况下用正则进行验证
    		array('sku_name','require','规格名不能为空'), //默认情况下用正则进行验证
    		array('sku_name','','规格名已存在',0,'unique',3), // 验证name字段是否唯一
    		array('sku_value','require','规格值不能为空'), //默认情况下用正则进行验证
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
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($ids){
		return $this->where(array('record_id'=>array('in',$ids)))->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->getField('sku_id, sku_value');
    }
    
    public function selectAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
}