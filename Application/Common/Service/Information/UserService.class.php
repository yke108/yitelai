<?php
namespace Common\Service\Information;
use Common\Basic\Status;

class UserService{
	
	public function getFieldData($map,$field,$bool=null){
		return $this->UserInfoDao()->getFieldRecord($map,$field,$bool);
	}
	
	public function getUserId($map){
		return $this->UserInfoDao()->getUserIdRecord($map);
	}
	
	public function getUser($map){
		return $this->UserInfoDao()->findUser($map);
	}
	
	public function userPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		if(!empty($params['keyword'])){
			$map['_string'] = " nick_name like '%$params[keyword]%' or mobile like '%$params[keyword]%'";
		}
		if(!empty($params['nick_name'])){
			$map['nick_name'] = array('like', '%'.$params['nick_name'].'%');
		}
		if(!empty($params['mobile'])){
			$map['mobile'] = array('like', '%'.$params['mobile'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['reg_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['reg_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$UserInfoDao = $this->UserInfoDao();
		$count = $UserInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'user_id desc' : $params['orderby'];
			$list = $UserInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function buyUsersPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$users = $this->orderInfoDao()->distinct(true)->field('user_id')->where(array('distributor_id'=>$params['distributor_id']))->select();
		if ($users) {
			$user_ids = array();
			foreach ($users as $v) {
				$user_ids[] = $v['user_id'];
			}
			$map = array(
					'user_id'=>array('in', $user_ids)
			);
			
			$UserInfoDao = $this->UserInfoDao();
			$count = $UserInfoDao->searchRecordsCount($map);
			
			$list = array();
			if($count > 0){
				$orderby = empty($params['orderby']) ? 'user_id desc' : $params['orderby'];
				$list = $UserInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			}
			
			return array(
					'list'=>$this->outputForList($list),
					'count'=>$count,
			);
		}
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			$info['avatar'] = $info['user_img'] ? picurl($info['user_img'], 'b120') : $info['headimgurl'];
			$info['birthday'] = explode('/', $info['birthday']);
			$info['mobile_hide'] = substr($info['mobile'], 0, 3).'****'.substr($info['mobile'], -4, 4);
			switch ($info['user_type']) {
				case 0: $info['user_typename'] = '普通会员'; break;
				case 1: $info['user_typename'] = '普通会员'; break;
				case 2: $info['user_typename'] = $info['status']==3?'分销员':'普通会员'; break;
				case 3: $info['user_typename'] = '业务员'; break;
			}
		}
		
		return $info;
	}
	
	public function getUserInfoByIdOrMobile($user_id, $mobile){
		$user = array();
		if($user_id > 0){
			$user = $this->getUserInfoById($user_id);
		}
		if($mobile && empty($user)){
			$user = $this->getUserInfoWithMobile($mobile);
		}
		return $user;
	}
	
	public function getUserInfoWithMobile($mobile){
		$map = array(
			'mobile'=>$mobile,
		);
		return $this->userInfoDao()->findUser($map);
	}
	
	public function getUserInfoWithOpenid($openid){
		$map = array(
			'openid'=>$openid,
		);
		return $this->userInfoDao()->findUser($map);
	}
	
	public function register($post, $user){
		$nick_name = substr($post['mobile'], 0, 3).'****'.substr($post['mobile'], -4, 4);
		$data = array(
			'mobile'=>$post['mobile'],
			'nick_name'=>$nick_name,
			'password'=>$this->userPwd($post['password']),
			'parent_id'=>$post['parent_id'],
			'user_type'=>$post['user_type'],
			//'apply_time'=>$post['user_type']==2?time():'',
			'distributor_id'=>$post['distributor_id'],
			'reg_time'=>time(),
			'last_login'=>time(),
			'last_ip'=>get_client_ip(),
			//'api_id'=>$post['ext']['api_id'],
			'from'=>$post['ext']['from'],
			//'version'=>$post['ext']['version'],
		);
		
		if($user['user_id'] > 0){
			if($this->updateUserInfo($user['user_id'], $data) !== false){
				return $user['user_id'];
			}
		} else {
			//$data['start_time'] = time();
			return $this->createUserInfo($data);
		}
		return false;
	}
	
	public function registerByWeixin($params){
		$openid = $params['openid'];
		if(empty($openid)) return array();
		$info = $this->getUserInfoWithOpenid($openid);
		if(!empty($info)) return $info;
		$data = array(
			'nick_name'=>$params['nickname'],
			'openid'=>$params['openid'],
			'headimgurl'=>$params['headimgurl'],
			'reg_time'=>time(),
			'last_login'=>time(),
			'last_ip'=>get_client_ip(),
			'sex' => $params['sex'],
			'subscribe'=>$params['subscribe'],
			'subscribe_time' => $params['subscribe_time'],
		);
		if ($params['clinic_id'] > 0){
			$clinic = $this->clinicInfoDao()->getClinic($params['clinic_id']);
			$data['parent_id'] = $clinic['user_id'];
			$data['my_clinic_id'] = $params['clinic_id'];
		}
		$id = $this->createUserInfo($data);
		if($id < 1) return array();
		return $this->getUserInfo($id);
	}
	
	public function modify($user, $data){
		return $this->updateUserInfo($user['user_id'], $data);
	}
	
	public function userPwdChk($user, $password){
		
		$passwd = $this->userPwd($password,$user['salt']);
		
		return $user['password'] == $passwd;
	}
	
	public function changeUserPwd($user, $password){
		$data = array(
			'password' =>$this->userPwd($password),
		);
		return $this->updateUserInfo($user['user_id'], $data);
	}
	
	public function changeUserPwdByMobile($phone, $password){
		$data = array(
			'password'=>$this->userPwd($password),
		);
		$map = array(
			'mobile'=>$phone,
		);
		return $this->userInfoDao()->where($map)->save($data);
	}
	
	public function changeTradePwd($user, $password, $phone = ''){
		$data = array(
			'trade_pwd' =>$this->tradePwd($password),
		);
		if(!$user['mobile'] && !empty($phone)){
			$data['mobile'] = $phone;
		}
		return $this->updateUserInfo($user['user_id'], $data);
	}
	
	public function tradePwdChk($user, $password){
		$passwd = $this->tradePwd($password);
		return $passwd == $user['trade_pwd'];
	}
	
	public function verifyCodeForTradePwdCheck($user, $password){
		$abc = explode('@', $password);
		if(count($abc) != 2) return false;
		if($abc[1] < time() - 60 * 5) return false;
		if($this->verifyCodeForTradePwd($user, $abc[1]) != $password) return false;
		return true;
	}
	
	public function verifyCodeForTradePwd($user, $time = ''){
		$t = empty($time) ? time() : $time;
		$pwd = md5(md5($user['user_id'].$t).$user['id_info'].$t);
		return $pwd.'@'.$t;
	}
	
	public function getCoinNumber($user_id){
		$map = array(
			'user_id'=>$user_id,
		);
		return $this->userInfoDao()->where($map)->getField('coin_number');
	}
	
	public function getList($params){
		$list = $this->userInfoDao()->where($params['_map'])
				->page($params['_page'], $params['_size'])
				->order($params['_order'])
				->select();
		if($params['_count']){
			$count = $this->userInfoDao()->where($params['_map'])->count();
		}
		return array(
			'list'=>$list,
			'count'=>$count,

		);
	}
	
	public function getCount($map){
		return $this->userInfoDao()->where($map)->count();
	}
	
	public function getUsers($ids){
		if(!is_array($ids) || count($ids) < 1) return array();
		$map = array(
			'user_id'=>array('in', $ids),
		);
		return $this->userInfoDao()->where($map)->getField('user_id, nick_name, user_img, mobile');
	}
	
    public function searchUsers($map, $orderBy, $start, $limit){
        return $this->userInfoDao()->searchUsers($map, $orderBy, $start, $limit);
    }
	
	public function searchUsersCount($map){
		return $this->userInfoDao()->searchRecordsCount($map);
    }
	
	public function idUsedCheck($id_no, $no_encrypt = true){
		$no_encrypt && $id_no = $this->_idEncrypt($id_no);
		$map = array(
			'id_verified'=>1,
			'id_info'=>$id_no,
		);
		return $this->userInfoDao()->where($map)->count();
	}
	
	public function idSave($user, $name, $id_no){
		$data = array(
			'real_name'=>$name,
			'id_info'=>$this->_idEncrypt($id_no),
		);
		return $this->updateUserInfo($user['user_id'], $data);
	}
	
	public function idVerified($user){
		$data = array(
			'id_verified'=>1,
		);
		return $this->updateUserInfo($user['user_id'], $data);
	}
	
	private function userPwd($password,$salt){
		return md5($password);
	}
	
	private function tradePwd($password){
		return md5($password);
	}
	
	private function updateUserInfo($user_id, $data){
		$map = array(
			'user_id'=>$user_id,
		);
        $result = $this->userInfoDao()->where($map)->save($data);
        return $result;
	}
	
	private function _idEncrypt($id_no){
		return substr($id_no, 0, 6).substr($id_no, -4).md5($id_no.'YiNGu');
	}
	
	private function createUserInfo($data){
		//$data['id_acc'] = rand(100, 999);
		return $this->userInfoDao()->add($data);
	}
	
	//用户信息
	public function getUserInfo($id){
		$map = array(
			'user_id'=>$id,
		);
		$info = $this->userInfoDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function juniorListNew($params){
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['user_id'])) {
			$map['parent_id'] = $params['user_id'];
		}
		
		$count = $this->userInfoDao()->where($map)->count();
		$list = array();
		if ($count) {
			$orderby = empty($params['orderby']) ? 'user_id desc' : $params['orderby'];
			$list = $this->UserInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
				'count'=>$count,
				'list'=>$list
		);
	}
	
	//余额支付（无支付记录）
	public function payByUserMoney($params){
		$user_info = $this->getUserInfo($params['user_id']);
		if ($params['money'] > $user_info['user_money']) throw new \Exception('余额不足');
		$data['user_money'] = array('exp','user_money-'.$params['money']);
		$res = $this->modify($user_info, $data);
		if (!$res) throw new \Exception('支付失败');
		return true;
	}
	
	//积分支付
	public function payByUserPayPoints($params){
		$user_info = $this->getUserInfo($params['user_id']);
		if ($params['pay_points'] > $user_info['pay_points']) throw new \Exception('积分不足');
		$data['pay_points'] = array('exp','pay_points-'.$params['pay_points']);
		$res = $this->modify($user_info, $data);
		if (!$res) throw new \Exception('支付失败');
		return true;
	}
	
	public function changePoint($params) {
		if(empty($params['user_id'])) throw new \Exception('缺少参数');
		if(intval($params['point']) <= 0) throw new \Exception('调整积分必须大于0');
		$user = $this->getUserInfo($params['user_id']);
		$params['point_old'] = $user['pay_points'];
		$params['user_name'] = $user['nick_name'];
		if ($params['change_type'] == 1) {
			$this->pointLogic()->plus($params);
		}else {
			$this->pointLogic()->reduct($params);
		}
	}
	
	//用户等级
	public function setRank($params){
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		//自己累积等级积分
		$res = $this->userInfoDao()->increaseRankPoints($user['user_id'], $params['rank_points']);
		if ($res === false) {
			return false;
		}
		
		//处理升级
		/* $rank_points = $user['rank_points'] + $params['rank_points'];
		$rank_id = 0;
		$rank_list = $this->userRankDao()->searchRecords();
		if ($rank_list) {
			foreach ($rank_list as $rank) {
				if ($rank_points >= $rank['min_points']) {
					$rank_id = $rank['rank_id'];
				}
			}
			$map = array('user_id'=>$user['user_id']);
			if($this->userInfoDao()->where($map)->save(array('rank_id'=>$rank_id)) === false) {
				return false;
			}
		} */
		
		return true;
	}
	
	private function userSessionDao(){
		return D('UserSession');
	}
	
	private function userInfoDao(){
		return D('Common/Information/User/Info');
	}
	
	private function smsCodeDao(){
		return D('SmsCode');
	}
	
	private function userAccountDao(){
		return D('Common/Information/User/Account');
	}
	
	private function pointLogic(){
		return D('Point', 'Logic');
	}
}