<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\User;
class UserService{
	
	public function getFieldData($map,$field,$bool=null){
		return $this->userInfoDao()->getFieldRecord($map,$field,$bool);
	}
	
	public function getUserId($map){
		return $this->userInfoDao()->getUserIdRecord($map);
	}
	
	public function getUser($map){
		return $this->userInfoDao()->findUser($map);
	}
	
	public function userCreateOrModify($params) {
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//负责品牌
		$data['brand_ids'] = $params['brand_ids'] ? implode(',', $params['brand_ids']) : '';
		$data['distributor_ids'] = $params['distributor_ids'] ? implode(',', $params['distributor_ids']) : '';
		
		$userInfoDao = $this->userInfoDao();
		if (!$userInfoDao->create($data)){
			throw new \Exception($userInfoDao->getError());
		}
		if ($params['user_id'] > 0){
			$result = $userInfoDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $userInfoDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function userPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		if(!empty($params['keyword'])){
			$map['nick_name|mobile'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if(!empty($params['nick_name'])){
			$map['nick_name'] = array('like', '%'.trim($params['nick_name']).'%');
		}
		if(!empty($params['mobile'])){
			$map['mobile'] = array('like', '%'.trim($params['mobile']).'%');
		}
		if (!empty($params['start_time'])) {
			$map['reg_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['reg_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if(!empty($params['parent_id'])){
			$map['parent_id'] = $params['parent_id'];
		}
		if(!empty($params['user_type'])){
			$map['user_type'] = $params['user_type'];
		}
		if(isset($params['store_id'])){
			$map['distributor_id'] = $params['store_id'];
		}
		if(isset($params['distributor_id'])){
			$map['distributor_id'] = $params['distributor_id'];
		}
		if(!empty($params['rank_id'])){
			$map['rank_id'] = $params['rank_id'];
		}
		if(!empty($params['city'])){
			$map['city'] = array('like', '%'.trim($params['city']).'%');
		}
		if(!empty($params['from'])){
			$map['from'] = $params['from'];
		}
		isset($params['admin_id']) && $map['admin_id'] = $params['admin_id'];
		//品牌
		if (isset($params['brand_id'])) {
			$where['brand_ids'] = array('like', '%,'.$params['brand_id'].',%');
			$distributor_list = $this->distributorInfoDao()->searchAllRecords($where);
			if (empty($distributor_list)) return array();
			$distributor_ids = array_keys($distributor_list);
			$map['distributor_id'] = array('in', $distributor_ids);
		}
		//需求
		if(!empty($params['demand'])){
			$map['demand'] = array('like', '%'.trim($params['demand']).'%');
		}
		
		$userInfoDao = $this->userInfoDao();
		$count = $userInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'user_id desc' : $params['orderby'];
			$list = $userInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function userAllList($map, $orderby){
		$list = $this->userInfoDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
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
			
			$userInfoDao = $this->userInfoDao();
			$count = $userInfoDao->searchRecordsCount($map);
			
			$list = array();
			if($count > 0){
				$orderby = empty($params['orderby']) ? 'user_id desc' : $params['orderby'];
				$list = $userInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			}
			
			return array(
					'list'=>$this->outputForList($list),
					'count'=>$count,
			);
		}
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $v) {
				$user_ids[] = $v['parent_id'];
				$distributor_ids[] = $v['distributor_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$distributors = $this->distributorInfoDao()->getFieldRecord(array('distributor_id'=>array('in', $distributor_ids)), 'distributor_id, distributor_name');
			
			foreach ($list as $k => $v) {
				//会员
				$list[$k]['avatar'] = $v['user_img'] ? picurl($v['user_img'], 'b90') : $v['headimgurl'];
				$list[$k]['user_name'] = $v['real_name'] ? $v['real_name'] : $v['nick_name'];
				//用户类型
				/* switch ($v['user_type']) {
					case 0: $list[$k]['user_typename'] = '会员'; break;
					case 1: $list[$k]['user_typename'] = '会员'; break;
					case 2: $list[$k]['user_typename'] = $v['status']==3?'分销员':'会员'; break;
					case 3: $list[$k]['user_typename'] = '业务员'; break;
				} */
				$list[$k]['user_typename'] = Status::$userTypeList[$v['user_type']];
				//上级业务员
				$list[$k]['salesman'] = $users[$v['parent_id']]['nick_name'];
				//所属店铺
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']];
				//分销员下级数量
				$list[$k]['junior_count'] = $this->userInfoDao()->where(array('parent_id'=>$v['user_id']))->count();
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			$info['avatar'] = $info['user_img'] ? picurl($info['user_img'], 'b120') : $info['headimgurl'];
			$info['user_name'] = $info['real_name'] ? $info['real_name'] : $info['nick_name'];
			$info['birthday'] = explode('/', $info['birthday']);
			$info['mobile_hide'] = substr($info['mobile'], 0, 3).'****'.substr($info['mobile'], -4, 4);
			$info['brand_ids'] = $info['brand_ids'] ? explode(',', $info['brand_ids']) : array();
			$info['distributor_ids'] = $info['distributor_ids'] ? explode(',', $info['distributor_ids']) : array();
			//用户类型
			/* switch ($info['user_type']) {
				case 0: $info['user_typename'] = '会员'; break;
				case 1: $info['user_typename'] = '会员'; break;
				case 2: $info['user_typename'] = $info['status']==3?'分销员':'会员'; break;
				case 3: $info['user_typename'] = '业务员'; break;
			} */
			$info['user_typename'] = Status::$userTypeList[$info['user_type']];
			//用户等级
			$info['rank'] = $this->userRankDao()->find($info['rank_id']);
			//地区
			if ($info['region_code']) {
				$info['region_name'] = $this->regionDao()->getRegionName($info['region_code']);
			}
		}
		
		return $info;
	}
	
	public function coinAccountChange($user, $number){
		if($number > 0){
			$exp3 = '+'.$number;
		} else {
			$exp3 = '-'.abs($number);
		}
		$data = array(
			'coin_number'=>array('exp', 'coin_number'.$exp3),
		);
		$result = $this->updateUserInfo($user['user_id'], $data);
        return $result;
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
		$nick_name = $post['nick_name'] ? $post['nick_name'] : substr($post['mobile'], 0, 3).'****'.substr($post['mobile'], -4, 4);
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
				'from_url'=>$post['ext']['from_url'],
				//'version'=>$post['ext']['version'],
				
				'subscribe'=>$post['subscribe'],
				'openid'=>$post['openid'],
				'sex'=>$post['sex'],
				'language'=>$post['language'],
				'city'=>$post['city'],
				'province'=>$post['province'],
				'country'=>$post['country'],
				'headimgurl'=>$post['headimgurl'],
				'subscribe_time'=>$post['subscribe_time'],
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
			'language'=>$params['language'],
			'city'=>$params['city'],
			'province'=>$params['province'],
			'country'=>$params['country'],
		);
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
	
	//申请分销员待审核
	public  function getAdminCount($params){
		//全部
		$all = $this->userInfoDao()->count();
	
		//待审核
		$map = array(
				'type'=>2,
				'status'=>0,
				'distributor_id'=>$params['distributor_id']
		);
		$nocheck = $this->userApplyDao()->where($map)->count();
		
		//平台待审核
		$map = array(
				'_string'=>"(type=1 and status=1) or (type=2 and status=1)",
		);
		$platform_nocheck = $this->userApplyDao()->where($map)->count();
		
		return array(
				'nocheck'=>$nocheck,
				'platform_nocheck'=>$platform_nocheck,
		);
	}
	
	public function userPwd($password,$salt){
		
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
			$list = $this->userInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
	
	//用户等级
	public function setRank($params){
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		//自己累积等级积分
		$res = $this->userInfoDao()->increaseRankPoints($user['user_id'], $params['rank_points']);
		if ($res === false) {
			return false;
		}
	
		//处理升级
		$rank_points = $user['rank_points'] + $params['rank_points'];
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
		}
	
		return true;
	}
	
	//提交分销员申请
	public function applySaleman($user_id){
		if(empty($user_id)){
			throw new \Exception('缺少参数');
		}
		$data=array('apply_time'=>time(),'status'=>0,'feedback'=>'','user_type'=>2);
		$result=$this->updateUserInfo($user_id,$data);
		if($result === false){
			throw new \Exception('提交申请失败');
		}
	}
	
	//
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
	
	//抢客户
	public function grabUser($params) {
		if (empty($params['user_id']) || empty($params['distributor_id']) || empty($params['admin_id'])) throw new \Exception('缺少参数');
		$user_info = $this->getUserInfo($params['user_id']);
		if (empty($user_info)) throw new \Exception('客户不存在');
		if ($user_info['distributor_id'] > 0) throw new \Exception('客户已被抢');
		
		M()->startTrans();
		
		//抢客户
		$data = array(
				'user_id'=>$user_info['user_id'],
				'distributor_id'=>$params['distributor_id'],
				'admin_id'=>$params['admin_id'],
				'grab_type'=>Status::GrabTypeApp,
				'grab_time'=>NOW_TIME,
		);
		$result = $this->userInfoDao()->saveRecord($data);
		if($result === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//统计抢客户次数
		$map = array('distributor_id'=>$params['distributor_id']);
		$data = array(
				'grab_times'=>array('exp', '`grab_times`+1'),
				'total_grab_times'=>array('exp', '`grab_times`+1'),
				'grab_time'=>NOW_TIME,
		);
		$result = $this->distributorInfoDao()->where($map)->save($data);
		if($result === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}

	public function userInfoService($user_id)
	{
		$where = array();
		$field = array('user_id','nick_name','real_name','user_img','headimgurl');
		$where['user_id'] = $user_id;
		$userInfoFind = $this->userInfoDao()->userInfoFind($where, $field);
		if($userInfoFind['nick_name']){
			$userInfoFind['user_name'] = $userInfoFind['nick_name'];
		} else {
			$userInfoFind['user_name'] = $userInfoFind['real_name'];
		}
		if($userInfoFind['user_img']){
			$userInfoFind['user_img'] = domain_name_url.'/upload/'.$userInfoFind['user_img'];
		} else {
			if($userInfoFind['headimgurl']){
				$userInfoFind['user_img'] = $userInfoFind['headimgurl'];
			} else {
				$userInfoFind['user_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
			}
		}
		return $userInfoFind;
	}

	public function userTotalCount($where = array()){
		return $this->userInfoDao()->where($where)->field(array('user_id'))->count();
	}

	public function userInfoListService($map = array(),$params = array()){
		$where = array();
		if($map['content']){
			$where[] = array(
				'nick_name' => array('like','%'.$map['content'].'%'),
				'real_name' => array('like','%'.$map['content'].'%'),
				'mobile' => array('like','%'.$map['content'].'%'),
				'email' => array('like','%'.$map['content'].'%'),
				'_logic' => 'or',
			);
		}
		if($map['user_type']){
			$where['user_type'] = $map['user_type'];
		}
		$field = array('user_id','nick_name','real_name','user_type','mobile','email','user_img','headimgurl');
		$orderBy = array('user_id' => 'DESC');
		$_list = array();
		$count = $this->userInfoDao()->where($where)->count();
		$data = $this->userInfoDao()->userInfoList($where, $field, $orderBy, $params['page'], $params['pagesize']);
		foreach($data as $key => $val){
			$_t = $val;
			$_t['user_type_name'] = User::$user_type[$val['user_type']];
			if($val['nick_name']){
				$_t['user_name'] = $val['nick_name'];
			} else {
				$_t['user_name'] = $val['real_name'];
			}
			if($val['user_img']){
				$_t['user_img'] = domain_name_url.'/upload/'.$val['user_img'];
			} else {
				if($val['headimgurl']){
					$_t['user_img'] = $val['headimgurl'];
				} else {
					$_t['user_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
				}
			}
			$_list[] = $_t;
		}
		return array(
			'count'=>$count,
			'list'=>$_list
		);
	}

	private function userSessionDao(){
		return D('UserSession');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userRankDao(){
		return D('Common/User/UserRank');
	}
	
	private function smsCodeDao(){
		return D('SmsCode');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function orderInfoDao(){
		return D('Common/Order/OrderInfo');
	}
	
	private function userApplyDao(){
		return D('Common/User/UserApply');
	}
	
	private function pointLogic(){
		return D('Point', 'Logic');
	}
	
	private function regionDao(){
		return D('Region');
	}
}