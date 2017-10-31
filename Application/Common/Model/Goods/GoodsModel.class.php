<?php
namespace Common\Model\Goods;
use Think\Model\RelationModel;

class GoodsModel extends RelationModel {
    protected $tableName = 'goods';
    protected $pk = 'goods_id';
    
    protected $_validate = array(
    		array('goods_sn','require','商品编号不能为空'), //默认情况下用正则进行验证
    		array('goods_sn','','商品编号已存在',0,'unique',3), // 验证goods_sn字段是否唯一
    		array('goods_name','require','商品名称不能为空'), //默认情况下用正则进行验证
    		array('goods_name','','商品名称已存在',0,'unique',3), // 验证goods_name字段是否唯一
    		array('cat_id','require','请选择商品分类'), //默认情况下用正则进行验证
    		array('brand_id','require','请选择商品品牌'), //默认情况下用正则进行验证
    		array('is_on_sale',array(0, 1),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		array('is_self_sale',array(0, 1),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		array('content','require','内容不能为空'), //默认情况下用正则进行验证
    		array('goods_image','require','商品主图不能为空'), //默认情况下用正则进行验证
    		array('goods_gallery','require','商品相册不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}

	public function goodsFind($map = array(), $field = array()){
		return $this->where($map)->field($field)->find();
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

	public function goodsListCount($where = array(), $filed = array()){
		return $this->where($where)->field($filed)->count();
	}

	public function goodsList($where = array(), $filed = array(), $orderBy = array(), $start, $limit){
		return $this->where($where)->field($filed)->order($orderBy)->page($start, $limit)->select();
	}

    public function searchRecord($map, $orderBy){
    	return $this->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
}