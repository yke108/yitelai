<?php
namespace Common\Model\User;
use Think\Model\RelationModel;

class UserInfoModel extends RelationModel {
    protected $tableName = 'user_info';
	//protected $_validate = array(     
//	 							array('verify','require','验证码必须！'),  // 都有时间都验证    
//	  							array('name','checkName','帐号错误！',1,'function',4),  // 只在登录时候验证     
//								array('password','checkPwd','密码错误！',1,'function',4), // 只在登录时候验证  
//								 );
	public function getFieldRecord($map,$field,$bool=null){
		return $this->where($map)->getField($field,$bool);
	}
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function getUserIdRecord($map){
		return $this->where($map)->getField("user_id",true);
	}
	
	public function findUser($map){
		return $this->where($map)->find();
	}

	public function userInfoFind($where = array(), $field = array()){
		return $this->where($where)->field($field)->find();
	}
	
	public function getUsersByIds($ids){
		$map['user_id'] = array('in', $ids);
		return $this->where($map)->getField("user_id, nick_name, real_name, mobile, user_img, headimgurl, user_type, city, region_code");
	}
	
	public function getUsers($ids){
		if(!is_array($ids) || count($ids) < 1) return array();
		$map = array(
			'user_id'=>array('in', $ids),
		);
		return $this->where($map)->getField("user_id, nick_name, real_name, mobile, user_img, headimgurl, city, region_code");
	}
	
	public function searchFieldRecords($map, $field = 'user_id, nick_name, real_name, mobile, user_img, headimgurl, city, region_code'){
		return $this->where($map)->getField($field);
	}
	
	public function addRecord($data){
		if (!empty($data['password'])){
			$data['salt'] = rand(1000,9999);
			$data['password'] = $this->password($data['password'], $data['salt']);
		}
		return $this->add($data);
	}
	
	public function saveRecord($data){
		if (!empty($data['password'])){
			$data['salt'] = rand(1000,9999);
			$data['password'] = $this->password($data['password'], $data['salt']);
		}
		
		return $this->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }

	public function userInfoList($where = array(), $field = array(), $orderBy = array(), $start, $limit){
		return $this->where($where)->field($field)->order($orderBy)->page($start, $limit)->select();
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function logined($user_id, $password = ''){
		$map = array(
			'user_id'=>$user_id,
		);
		if ($password){
			$data['salt'] = rand(1000,99999);
			$data['password'] = $this->password($password, $data['salt']);
		}
		return $this->where($map)->save($data);
	}
	
	public function password($password, $salt = ''){
		$str = md5(md5($password).$salt);
		$str = md5(substr($str, 3, 26));
		return $str;
	}
	
	public function increaseUserMoney($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('user_money', $money);
	}
	
	public function increasePayReward($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('pay_reward', $money);
	}
	
	public function increaseGetReward($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('get_reward', $money);
	}
	
	public function increaseCommissionMoney($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('commission_money', $money);
	}
	
	public function increaseFrozenRechargeMoney($user_id,$money){
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('frozen_recharge_money', $money);
	}
	
	public function increaseFrozenMoney($user_id,$money){
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('frozen_money', $money);
	}
	
	public function depleteMoney($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setDec('user_money', $money);
	}
	
	public function depleteFrozenMoney($user_id, $money) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setDec('frozen_money', $money);
	}
	
	public function increasePayPoints($user_id, $pay_points) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('pay_points', $pay_points);
	}
	
	public function depletePayPoints($user_id, $pay_points) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setDec('pay_points', $pay_points);
	}
	
	//增加等级积分
	public function increaseRankPoints($user_id, $rank_points) {
		$map = array('user_id'=>$user_id);
		return $this->where($map)->setInc('rank_points', $rank_points);
	}
}