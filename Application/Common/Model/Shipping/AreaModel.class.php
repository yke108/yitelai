<?php
namespace Common\Model\Shipping;
use Think\Model\RelationModel;

class AreaModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'shipping_area';
    protected $pk = 'shipping_area_id';
    
    protected $_validate = array(
    		array('shipping_area_name','require','配送区域名称不能为空'), //默认情况下用正则进行验证
    		array('distributor_id,region_code_list','','配送区域已存在',0,'unique',1), // 在新增的时候验证name字段是否唯一
    		array('status',array(1,2),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    );
    
    protected $_auto = array (
    		array('status','1'),  // 新增的时候把status字段设置为1
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function delRecord($map){
		return $this->where($map)->delete();
	}
	
	public function delRecords($ids){
		return $this->where(array('cat_id'=>array('in',$ids)))->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
    
    public function searchRecordsField($map){
    	return $this->where($map)->getField('cat_id,cat_name');
    }
}