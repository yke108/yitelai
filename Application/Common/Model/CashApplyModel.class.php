<?php
namespace Common\Model;
use Think\Model\RelationModel;

class CashApplyModel extends RelationModel {
    protected $tableName = 'cash_apply';
    protected $pk = 'apply_id';
    
    protected $_validate = array(
    		array('money','check1','提现金额不能为空',0,'callback '), // 自定义函数验证
    		array('money','check2','每次提现的金额最多为200元',0,'callback '), // 自定义函数验证
    );
    
    protected $_auto = array (
    		//array('add_time','time',1,'function'), // 对add_time字段在添加的时候写入当前时间戳
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
        return $this->alias('a')->field('a.*,b.*,a.status apply_status,a.remark apply_remark') 
				->join('LEFT JOIN __USER_INFO__ b ON b.user_id=a.user_id')
        		->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->alias('a')
				->join('LEFT JOIN __USER_INFO__ b ON b.user_id=a.user_id')
				->where($map)->count();
    }
    
    public function check1($money){
    	if ($money <= 0) {
    		return false;
    	}
    	return true;
    }
    
    public function check2($money){
    	if ($money > 200) {
    		return false;
    	}
    	return true;
    }
}