<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class DistributorRankModel extends RelationModel {
    protected $tableName = 'distributor_rank';
    
	protected $_validate = array(     
			array('rank_name','require','等级名称不能为空',1),
			//array('discount','require','折扣不能为空',1),
			//array('discount','checkDiscount','折扣必须为1-100的整数',2,'callback'), // 自定义函数验证密码格式
	);
	
	public function checkDiscount($discount){
		$discount = intval($discount);
		if ($discount < 1 || $discount > 100) {
			return false;
		}else {
			return true;
		}
	}
	
	public function findRecord($id){
   		return $this->find($id);
	}
	
	public function getRecord($map){
		return $this->where($map)->find();
	}
	
	public function getFieldRecord($map, $field = 'rank_id, rank_name, discount', $bool=null){
		return $this->where($map)->getField($field, $bool);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function searchAllRecords($map, $orderBy){
		return $this->where($map)->order($orderBy)->select();
	}
}