<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class CashApplyModel extends RelationModel {
    protected $tableName = 'distributor_cash_apply';
    protected $pk = 'apply_id';
    
    protected $_validate = array(
    		array('money','check1','提现金额不能为空',0,'callback'), // 自定义函数验证
    		array('region_code','check2','所在省市不能为空',0,'callback'), // 自定义函数验证
    		array('open_bank','require','开户支行不能为空',1),
    		array('open_name','require','开户名不能为空',1),
    		array('card','require','银行卡号不能为空',1),
    		array('card','/^[1-9][0-9]{15,18}/','银行卡号格式不正确'),
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
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function check1($money){
    	if ($money <= 0) {
    		return false;
    	}
    	return true;
    }
    
    public function check2($region_code){
    	if (empty($region_code)) {
    		return false;
    	}
    	return true;
    }
}