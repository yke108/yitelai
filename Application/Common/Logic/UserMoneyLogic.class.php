<?php
namespace Common\Logic;
use Common\Basic\Genre;
class UserMoneyLogic{
	public function winMoneyOnLottery($params){
		$p = array(
			'user_id'=>$params['user_id'],
			'money'=>$params['money'],
			'type'=>16,
			'desc'=>$params['desc'],
		);
		if ($this->winMoney($p) === false) return false;
		return true;
	}
	
	public function winMoney($params){
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		if (empty($user)) return false;
		$res = $this->userInfoDao()->increaseUserMoney($params['user_id'], $params['money']);
		if($res === false){
			return false;
		}
		
		$data = array(
			'user_id'=>$params['user_id'],
			'amount_old'=>$user['user_money'],
			'amount_change'=>$params['money'],
			'change_time'=>NOW_TIME,
			'change_desc'=>$params['desc'],
			'change_type'=>$params['type'],
			'account_type'=>1,
			'ref_user_id'=>0,
			'ref_id'=>$params['ref_id'] * 1,
		);
		$res = $this->userAccountDao()->addRecord($data);
		if($res < 1){
			return false;
		}
		return true;
	}
	
	protected function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
}