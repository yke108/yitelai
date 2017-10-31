<?php
namespace Common\Logic;

class PointLogic {
	const PointTypeReg = 1; //注册赠送积分
	const PointTypeLogin = 2; //登录送积分
	const PointTypeIntroduce = 3; //介绍赠送积分
	const PointTypeLike = 4; //点赞赠送积分
	const PointTypeBirthday = 5; //生日购买送积分
	const PointTypeShare = 6; //分享赠送积分
	const PointTypeBuyGoods = 7; //购买商品送积分
	const PointTypeJuniorBuyGoods = 8; //推荐购买商品赠送积分
	const PointTypeExchange = 9; //积分兑换商品
	const PointTypePlus = 11; //人工添加积分
	const PointTypeReduct = 12; //人工减少积分
	const PointTypeLottery = 13; //抽奖获得积分
	const PointTypePayOrder = 21; //支付订单
	const PointTypePlayLottery = 22; //获取抽奖机会
	const PointTypeHitMouse = 23; //打地鼠获取积分
	const PointTypeDownLoad = 24; //下载图库减少积分
	const PointTypeComment = 25; //评论送积分
	const PointTypeCollect = 26; //关注送积分
	const PointTypeRead = 27; //阅读送积分
	const PointTypePublish = 28; //发表文章送积分
	
	static $ctypes = array(
			self::PointTypeReg=>'注册赠送积分',
			self::PointTypeLogin=>'登录送积分',
			self::PointTypeIntroduce=>'介绍赠送积分',
			self::PointTypeLike=>'点赞赠送积分',
			self::PointTypeBirthday=>'生日购买送积分',
			self::PointTypeShare=>'分享赠送积分',
			self::PointTypeBuyGoods=>'购买商品送积分',
			self::PointTypeJuniorBuyGoods=>'推荐购买商品赠送积分',
			self::PointTypeExchange=>'积分兑换商品',
			self::PointTypePlus=>'人工添加积分',
			self::PointTypeReduct=>'人工减少积分',
			self::PointTypeLottery=>'抽奖获得积分',
			self::PointTypePayOrder=>'支付订单',
			self::PointTypePlayLottery=>'获取抽奖机会',
			self::PointTypeHitMouse=>'打地鼠获取积分',
			self::PointTypeDownLoad=>'下载图库减少积分',
			self::PointTypeComment=>'评论送积分',
			self::PointTypeCollect=>'关注送积分',
			self::PointTypeRead=>'阅读送积分',
			self::PointTypePublish=>'发表文章送积分',
	);
	
	public function add($params){
		M()->startTrans();
		
		//增加用户积分
		$result = $this->UserInfoDao()->increasePayPoints($params['user_id'], $params['point']);
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		//等级积分
		$params_rank = array(
				'user_id'=>$params['user_id'],
				'rank_points'=>$params['point']
		);
		$result = $this->userService()->setRank($params_rank);
		if($result === false){
			$this->error('赠送等级积分失败');
		}
		
		//积分记录
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
    	$result = $this->messageDao()->add($data);
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
    	return D('Common/User/UserInfo');
    }
    
    private function PointLogDao(){
    	return D('Common/Point/PointLog');
    }
    
    private function messageDao(){
    	return D('Message');
    }
    
    private function userService(){
    	return D('User', 'Service');
    }
}