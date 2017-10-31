<?php
namespace Common\Model\Goods;
use Think\Model\RelationModel;

class GoodsProductModel extends RelationModel {
    protected $tableName = 'goods_product';
    protected $pk = 'product_id';
    
    protected $_validate = array(
    		array('product_name','require','货品名称不能为空'), //默认情况下用正则进行验证
    		array('product_name,goods_id','','货品名称已存在',0,'unique',3), // 在新增的时候验证name字段是否唯一
    		array('product_price','require','货品价格不能为空'), //默认情况下用正则进行验证
    		array('market_price','require','市场参考价不能为空'), //默认情况下用正则进行验证
    		array('product_weight','require','货品重量不能为空'), //默认情况下用正则进行验证
    		array('stock_num','require','库存数量不能为空'), //默认情况下用正则进行验证
    		array('product_image','require','货品图片不能为空'), //默认情况下用正则进行验证
    		//array('value',array(1,2,3),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		//array('repassword','password','确认密码不正确',0,'confirm'), // 验证确认密码是否和密码一致
    		//array('password','checkPwd','密码格式不正确',0,'function'), // 自定义函数验证密码格式
    );
    
    protected $_auto = array (
    		//array('status','1'),  // 新增的时候把status字段设置为1
    		//array('password','md5',3,'function') , // 对password字段在新增和编辑的时候使md5函数处理
    		//array('name','getName',3,'callback'), // 对name字段在新增和编辑的时候回调getName方法
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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
    
    public function searchRecord($map, $orderBy){
    	return $this->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
}