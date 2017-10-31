<?php
namespace Common\Model\Shipping;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'shipping_info';
    protected $pk = 'shipping_id';
    
    protected $_validate = array(
    		array('distributor_id','require','分销商ID不能为空'), //默认情况下用正则进行验证
    		array('shipping_name','require','配送方式名称名称不能为空'), //默认情况下用正则进行验证
    		array('shipping_code','require','请选择快递公司'), //默认情况下用正则进行验证
    		array('distributor_id,shipping_code','','配送方式已存在',0,'unique',3), // 验证字段是否唯一
    		array('enabled',array(0,1,2),'配送方式状态的值不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    );
    
    protected $_auto = array (
    		array('enabled','1'),  // 新增的时候把enabled字段设置为1
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
		return $this->where(array('distributor_id'=>array('in',$ids)))->delete();
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
}