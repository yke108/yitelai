<?php
namespace Common\Model\User;
use Think\Model;

class UserAccountModel extends Model{
	const AccountTypeMoney = 1;
	//const AccountTypePoints = 2;
	const ChangeTypePayOrder = 10;
	const ChangeTypeCommission = 11;
	const ChangeTypeCashApply = 12;
	const ChangeTypeBackCashApply = 13;
	const ChangeTypeOrderBack = 14;
	const ChangeTypeOrderTeamBack=15;
	//const ChangeTypePayPoints = 15;
	const ChangeTypeLottery = 16;
	const ChangeTypeRecharge=17;
	const ChangeTypeReward=18;
	const ChangeTypeGetReward=19;
	const ChangeTypePayDeposit=20;
	const ChangeTypePayService=21;
	
	static $ctypes = array(
			10=>'支付订单',
			11=>'获得分利',
			12=>'申请提现',
			13=>'提现失败',
			14=>'退货退款',
			15=>'拼团失败退款',
			//15=>'获得积分',
			16=>'抽奖获得',
			17=>'充值',
			18=>'文章打赏',
			19=>'获得打赏',
			20=>'支付保证金',
			21=>'支付技术服务费',
	);
	
	public function getFieldRecord($map,$field='id,user_id,amount_old,amount_change,change_desc,change_type'){
		return $this->where($map)->getField($field);
	}
	
	public function getCtypes(){
		return self::$ctypes;
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
    public function searchRecords($map, $orderBy, $start, $limit){
        return $this->alias('a')->field('a.*, b.nick_name, b.mobile,b.user_img')
				->join('LEFT JOIN __USER_INFO__ b ON a.user_id = b.user_id')
				->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->alias('a')
				->join('LEFT JOIN __USER_INFO__ b ON a.user_id = b.user_id')
				->where($map)->count();
    }
    
    public function searchRecordsSum($map){
		return $this->alias('a')
		    	->join('LEFT JOIN __USER_INFO__ b ON a.user_id = b.user_id')
		    	->where($map)->sum('amount_change');
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->alias('a')->field('a.*, b.nick_name, b.mobile,b.user_img')
    	->join('LEFT JOIN __USER_INFO__ b ON a.user_id = b.user_id')
    	->where($map)->order($orderBy)->select();
    }
	
	public function payOrderByUserMoney($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['order_amount'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypePayOrder],
				'change_type'=>self::ChangeTypePayOrder,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['order_id'],
		);
		return $this->add($data);
	}
	
	public function getCommissionMoney($params){
		$data = array(
				'distributor_id'=>$params['distributor_id'],
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['commission_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeCommission],
				'change_type'=>self::ChangeTypeCommission,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['order_id'],
		);
		return $this->add($data);
	}
	
	public function cashApply($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeCashApply],
				'change_type'=>self::ChangeTypeCashApply,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['apply_id'],
		);
		return $this->add($data);
	}
	
	public function backCashApplyMoney($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeBackCashApply],
				'change_type'=>self::ChangeTypeBackCashApply,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['apply_id'],
		);
		return $this->add($data);
	}
	
	public function backOrder($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeOrderBack],
				'change_type'=>self::ChangeTypeOrderBack,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['order_id'],
		);
		return $this->add($data);
	}
	
	//拼团退款记录
	public function OrderTeamBack($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeOrderTeamBack],
				'change_type'=>self::ChangeTypeOrderTeamBack,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['back_id'],
		);
		return $this->add($data);
	}
	
	//充值
	public function RechargeLog($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['user_money'],
				'amount_change'=>$params['money'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeRecharge],
				'change_type'=>self::ChangeTypeRecharge,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>'',
				'ref_id'=>'',
		);
		return $this->add($data);
	}
	
	/* public function getPayPoints($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['pay_points'],
				'amount_change'=>$params['user_pay_points'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypePayPoints],
				'change_type'=>self::ChangeTypePayPoints,
				'account_type'=>self::AccountTypePoints,
				'ref_id'=>$params['order_id'],
		);
		return $this->add($data);
	} */
	
	//打赏
	public function payReward($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['amount_old'],
				'amount_change'=>$params['amount_change'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeReward],
				'change_type'=>self::ChangeTypeReward,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['ref_id'],
		);
		return $this->add($data);
	}
	
	//获得打赏
	public function getReward($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['amount_old'],
				'amount_change'=>$params['amount_change'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypeGetReward],
				'change_type'=>self::ChangeTypeGetReward,
				'account_type'=>self::AccountTypeMoney,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['ref_id'],
		);
		return $this->add($data);
	}
	
	//支付保证金
	public function payDeposit($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['amount_old'],
				'amount_change'=>$params['amount_change'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypePayDeposit],
				'change_type'=>self::ChangeTypePayDeposit,
				'account_type'=>self::AccountTypeMoney,
				'ref_id'=>$params['ref_id'],
		);
		return $this->add($data);
	}
	
	//支付技术服务费
	public function payService($params){
		$data = array(
				'user_id'=>$params['user_id'],
				'amount_old'=>$params['amount_old'],
				'amount_change'=>$params['amount_change'],
				'change_time'=>NOW_TIME,
				'change_desc'=>self::$ctypes[self::ChangeTypePayService],
				'change_type'=>self::ChangeTypePayService,
				'account_type'=>self::AccountTypeMoney,
				'ref_id'=>$params['ref_id'],
		);
		return $this->add($data);
	}
}