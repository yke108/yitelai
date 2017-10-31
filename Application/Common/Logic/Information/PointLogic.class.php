<?php
namespace Common\Logic\Information;

class PointLogic {
	const PointTypeReg = 1;
	const PointTypeLogin = 2;
	const PointTypeShare = 3;
	const PointTypeLike = 4;
	const PointTypeComment = 5;
	
	static $ctypes = array(
			1=>'注册赠送积分',
			2=>'登录送积分',
			3=>'分享赠送积分',
			4=>'点赞赠送积分',
			5=>'评论送积分',
	);
	
	public function add($params){
		M()->startTrans();
		
		$result = $this->UserInfoDao()->increasePayPoints($params['user_id'], $params['point']);
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		$data = array(
				'user_id'=>$params['user_id'],
				'point_old'=>$params['point_old'],
				'point_change'=>$params['point'],
				'change_type'=>$params['type'],
				'change_desc'=>self::$ctypes[$params['type']],
				'add_time'=>NOW_TIME,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['ref_id']
		);
		if (!$this->PointLogDao()->create($data)){
			M()->rollback();
			throw new \Exception($this->PointLogDao()->getError());
		}
		$result = $this->PointLogDao()->add();
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		M()->commit();
		
		return true;
	}
	
	public function reduce($params){
		M()->startTrans();
	
		$result = $this->UserInfoDao()->depletePayPoints($params['user_id'], $params['point']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('减少会员积分失败');
		}
		
		$data = array(
				'user_id'=>$params['user_id'],
				'point_old'=>$params['point_old'],
				'point_change'=>$params['point'] * -1,
				'change_type'=>$params['type'],
				'change_desc'=>self::$ctypes[$params['type']],
				'add_time'=>NOW_TIME,
				'ref_user_id'=>$params['ref_user_id'],
				'ref_id'=>$params['ref_id']
		);
		if (!$this->PointLogDao()->create($data)){
			M()->rollback();
			throw new \Exception($this->PointLogDao()->getError());
		}
		$result = $this->PointLogDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('添加积分记录失败');
		}
		
		M()->commit();
	
		return true;
	}
	
    public function plus($params){
    	M()->startTrans();
    	
    	$result = $this->UserInfoDao()->increasePayPoints($params['user_id'], $params['point']);
    	if ($result === false) {
    		M()->rollback();
    		throw new \Exception('增加会员积分失败');
    	}
    	
    	$data = array(
    			'user_id'=>$params['user_id'],
    			'point_old'=>$params['point_old'],
    			'point_change'=>$params['point'],
    			'change_type'=>self::PointTypePlus,
    			'change_desc'=>self::$ctypes[self::PointTypePlus],
    			'add_time'=>NOW_TIME
    	);
    	if (!$this->PointLogDao()->create($data)){
    		M()->rollback();
    		throw new \Exception($this->PointLogDao()->getError());
    	}
    	$result = $this->PointLogDao()->addRecord($data);
    	if ($result === false) {
    		M()->rollback();
    		throw new \Exception('添加积分记录失败');
    	}
    	
    	M()->commit();
    	
    	return true;
    }
    
    public function reduct($params){
    	M()->startTrans();
    	
    	//减少会员积分
    	$result = $this->UserInfoDao()->depletePayPoints($params['user_id'], $params['point']);
    	if ($result === false) {
    		M()->rollback();
    		throw new \Exception('减少会员积分失败');
    	}
    	
    	//添加积分记录
    	$data = array(
    			'user_id'=>$params['user_id'],
    			'point_old'=>$params['point_old'],
    			'point_change'=>$params['point'] * -1,
    			'change_type'=>self::PointTypeReduct,
    			'change_desc'=>self::$ctypes[self::PointTypeReduct],
    			'add_time'=>NOW_TIME
    	);
    	if (!$this->PointLogDao()->create($data)){
    		M()->rollback();
    		throw new \Exception($this->PointLogDao()->getError());
    	}
    	$result = $this->PointLogDao()->addRecord($data);
    	if ($result === false) {
    		M()->rollback();
    		throw new \Exception('添加积分记录失败');
    	}
    	
    	//添加会员消息
    	$data = array(
    			'title'=>self::$ctypes[self::PointTypeReduct],
    			'user_id'=>$params['user_id'],
    			'add_time'=>NOW_TIME,
    			'type'=>1,
    			'user_name'=>$params['user_name'],
    			'content'=>$params['content'],
    	);
    	$result = $this->userMsgDao()->add($data);
    	if ($result === false) {
    		M()->rollback();
    		throw new \Exception('添加会员消息失败');
    	}
    	
    	M()->commit();
    	
    	return true;
    }
    
	public function playLottery($params){
		$user = $this->UserInfoDao()->getRecord($params['user_id']);
		if (empty($user)) return false;
		$result = $this->UserInfoDao()->depletePayPoints($params['user_id'], $params['points']);
		if ($result === false) return false;
		$data = array(
    		'user_id'=>$params['user_id'],
    		'point_old'=>$user['pay_points'],
    		'point_change'=>$params['points'] * (-1),
    		'change_type'=>self::PointTypePlayLottery,
    		'change_desc'=>'获取抽奖机会',
    		'add_time'=>NOW_TIME
    	);
    	$result = $this->PointLogDao()->addRecord($data);
    	if ($result === false) return false;
		return true;
	}
    
	public function winPointsOnLottery($params){
		$p = array(
			'user_id'=>$params['user_id'],
			'points'=>$params['points'],
			'type'=>self::PointTypeLottery,
			'desc'=>$params['desc'],
		);
		if ($this->winPoints($p) === false) return false;
		return true;
	}
	
	public function winPoints($params){
		$user = $this->UserInfoDao()->getRecord($params['user_id']);
		if (empty($user)) return false;
		$data = array(
			'user_id'=>$params['user_id'],
			'pay_points'=>array('exp', 'pay_points+'.$params['points']),
		);
		$result = $this->UserInfoDao()->increasePayPoints($params['user_id'], $params['points']);
		if ($result === false) {
			return false;
		}
		
		$data = array(
    		'user_id'=>$params['user_id'],
    		'point_old'=>$user['pay_points'],
    		'point_change'=>$params['points'],
    		'change_type'=>$params['type'],
    		'change_desc'=>$params['desc'],
    		'add_time'=>NOW_TIME
    	);
    	$result = $this->PointLogDao()->addRecord($data);
    	if ($result === false) {
    		return false;
    	}
		return true;
	}
    
    private function UserInfoDao(){
    	return D('Common/Information/User/Info');
    }
    
    private function PointLogDao(){
    	return D('Common/Information/Point/Log');
    }
    
    private function userMsgDao(){
    	return D('Common/Information/User/Msg');
    }
}