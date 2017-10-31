<?php
namespace Wap\Controller\Index;
use Wap\Controller\WapController;
use Common\Logic\PointLogic;
use Common\Basic\Status;

class SiteController extends WapController {
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '登录注册');
    }
	
	public function loginAction(){
		if ($this->user['user_id']) {
			$this->redirect('user/index/index');
		}
		if(IS_POST){
			$post = I('post.');
			$mobile = $post['mobile'];
			$password = $post['pwd'];
			$userService = $this->userService();
			$user = $userService->getUserInfoWithMobile($mobile);
			if(!$userService->userPwdChk($user, $password)){
				$this->error('用户名或密码错误');
			}
			
			//登录赠送积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			if(date('Ymd') != date('Ymd',$user['last_login'])){
				$params = array(
						'user_id'=>$user['user_id'],
						'point'=>$point_config['login']['fval'],
						'type'=>PointLogic::PointTypeLogin,
						//'ref_user_id'=>$user['user_id'],
						'ref_id'=>$user['user_id']
				);
				$result = $this->pointService()->addUserPoint($params);
				if($result === false){
					$this->error('赠送积分失败');
				}
			}
			
			//等级积分
			$params = array(
					'user_id'=>$user['user_id'],
					'rank_points'=>$point_config['login']['fval']
			);
			$result = $this->userService()->setRank($params);
			if($result === false){
				$this->error('赠送等级积分失败');
			}
			
			//更新登录时间和IP
			$data = array(
					'last_login'=>NOW_TIME,
					'last_ip'=>get_client_ip()
			);
			if (!$userService->modify($user, $data)) {
				$this->error('登录失败');
			}
			
			session('userid', $user['user_id']);
			
			$wap_from_url = session('wap_from_url') ? session('wap_from_url') : U('user/index');
			$this->success('登录成功', $wap_from_url);
		}
		$this->display();
	}
	
	public function codeAction(){
		$post = I('post.');
		$phone = trim($post['phone']);
		//$type = intval(trim($post['type']));
		//if($type < 1) $this->error('发送失败');
		$type = 1;
		$user = $this->userService()->getUserInfoWithMobile($phone);
		$result = $this->smsService()->sendSms($user, $phone, $type);
		if(!is_array($result)){
			$this->error($result);
		}
		
		//过滤
		$filter = array('13912345678', '13719148993');
		if (in_array($phone, $filter)) {
			$this->success('验证码发送成功', '', $result);
		}
		
		$this->success('验证码发送成功', '', array('sms_id'=>$result['sms_id']));
	}
	
	public function getForgetCodeAction(){
		$post = I('post.');
		$phone = trim($post['phone']);
		$type = 4;
		$user = $this->userService()->getUserInfoWithMobile($phone);
		$result = $this->smsService()->sendSms($user, $phone, $type);
		if(!is_array($result)){
			$this->error($result);
		}
	
		//过滤
		$filter = array('13912345678');
		if (in_array($phone, $filter)) {
			$this->success('验证码发送成功', '', $result);
		}
	
		$this->success('验证码发送成功', '', array('sms_id'=>$result['sms_id']));
	}
	
	public function loginphoneAction(){
		$this->display();
	}
	
	public function regAction(){
		if ($this->user['user_id']) {
			$this->redirect('user/index/index');
		}
		
		$get = I('get.');
		
		if (IS_POST) {
			$post = I('post.');
			extract($post);
			if(empty($username) || empty($pwd)){
				$this->error('用户名或密码不能为空');
			}
			
			//查看格式是否正确
			$username = trim($username);
			$password = trim($pwd);
			if(preg_match('/^[0-9]{11}$/is', $username) < 1){
				$this->error('不是有效的手机号码');
			}
			$type = 1;
			$check = $this->smsService()->getCheckedRecord($sms_id, $code, $type);
			if(empty($check)) {
				$this->error('验证码错误');
			}
			
			$usobj = $this->userService();
			
			//根据invite找到推荐人
			$inviter_id = 0;
			$invite = trim($post['invite']);
			if(strlen($invite) == 11){
				$invitor = $usobj->getUserInfoWithMobile($invite);
				if(empty($invitor)){
					$this->error('邀请人不存在');
				}
				$inviter_id = $invitor['user_id'];
				//$user_type = $invitor['user_type'] == 3 ? 2 : 1;
				$user_type = Status::UserTypeDistributorman;
				$distributor_id = $invitor['distributor_id'];
			}
			
			$user = $usobj->getUserInfoWithMobile($username);
			$data = array(
					'mobile'=>$username,
					'password'=>$password,
					'parent_id'=>$inviter_id,
					'user_type'=>$user_type,
					'distributor_id'=>$distributor_id,
					'ext'=>array(
						'from'=>Status::FromWeixin,
						'from_url'=>session('wap_from_url') ? session('wap_from_url') : __SELF__,
						//'version'=>'1',
					),
					//'from'=>'weixin',
					//'openid'=>cookie($this->openid_ck),
			);
			
			//微信信息
			$openid = cookie($this->openid_ck);
			if ($openid) {
				$config = $this->configService()->findWeixinConfigs();
				$access_token = $this->weixinService()->getAccessToken($config['js_app_id'], $config['js_app_secret']);
				$jssdk = new \Common\Component\Weixin\Wxjssdk($config['js_app_id'], $config['js_app_secret'],$access_token);
				$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}";
				$userinfo_json = $jssdk->httpGet($url);
				$userinfo = json_decode($userinfo_json,true);
				if ($userinfo['errcode'] > 0) {
					$this->error($userinfo['errmsg']);
				}
				$data['subscribe'] = $userinfo['subscribe'];
				$data['openid'] = $userinfo['openid'];
				$data['nick_name'] = $userinfo['nickname'];
				$data['sex'] = $userinfo['sex'];
				$data['language'] = $userinfo['language'];
				$data['city'] = $userinfo['city'];
				$data['province'] = $userinfo['province'];
				$data['country'] = $userinfo['country'];
				$data['headimgurl'] = $userinfo['headimgurl'];
				$data['subscribe_time'] = $userinfo['subscribe_time'];
				
				$user = $usobj->getUserInfoWithOpenid($data['openid']);
			}
			
			//高德API
			$rest = curl_get('http://restapi.amap.com/v3/ip?key=81131309bbab0c3362b60e87ca82c6f0');
			if ($rest->status == 1) {
				$data['province'] = $rest->province;
				$data['city'] = $rest->city;
				$data['region_code'] = $rest->adcode;
			}
			
			M()->startTrans();
			
			$user_id = $usobj->register($data, $user);
			if($user_id < 1){
				M()->rollback();
				$this->error('注册失败');
			}
			
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			
			//介绍赠送积分
			if ($inviter_id > 0) {
				$params = array(
						'user_id'=>$inviter_id,
						'point'=>$point_config['introduce']['fval'],
						'type'=>PointLogic::PointTypeIntroduce,
						'ref_user_id'=>$user_id,
						'ref_id'=>$user_id
				);
				$result = $this->pointService()->addUserPoint($params);
				if($result === false){
					M()->rollback();
					$this->error('赠送积分失败');
				}
				
				//等级积分
				$params = array(
						'user_id'=>$user_id,
						'rank_points'=>$point_config['introduce']['fval']
				);
				$result = $this->userService()->setRank($params);
				if($result === false){
					$this->error('赠送等级积分失败');
				}
			}
			
			//注册赠送积分
			$params = array(
					'user_id'=>$user_id,
					'point'=>$point_config['reg']['fval'],
					'type'=>PointLogic::PointTypeReg,
					//'ref_user_id'=>$user_id,
					'ref_id'=>$user_id
			);
			$result = $this->pointService()->addUserPoint($params);
			if($result === false){
				M()->rollback();
				$this->error('赠送积分失败');
			}
			
			//等级积分
			$params = array(
					'user_id'=>$user_id,
					'rank_points'=>$point_config['reg']['fval']
			);
			$result = $this->userService()->setRank($params);
			if($result === false){
				$this->error('赠送等级积分失败');
			}
			
			M()->commit();
			
			session('userid', $user_id);
			$back_url = session('wap_from_url') ? session('wap_from_url') : U('user/index');
			$this->success('注册成功', $back_url);
		}
		
		//是否有推荐人
		$uid = $get['uid'] ? $get['uid'] : cookie($this->uid);
		if ($uid) {
			$inviter = $this->userService()->getUserInfo($uid);
			$this->assign('inviter', $inviter);
		}
		
		$this->display();
	}
	
	public function logoutAction(){
		//session('user', null);
		unset($_SESSION['wxuser']);
		$from_url = session('wap_from_url') ? session('wap_from_url') : U('index/site/login');
		$this->success('退出成功', $from_url);
	}
	
	public function forgetAction(){
		if ($this->user['user_id']) {
			$this->redirect('user/index/index');
		}
		
		if(IS_POST){
			$post = I('post.');
			if(strlen(trim($post['password'])) < 6 
			|| $post['password'] != $post['password2']){
				$this->error('密码不正确');
			}
			//读取数据
			$type = 4;
			$sms = $this->smsService()->getCheckedRecord($post['sms_id'], $post['code'], $type);
			if(empty($sms)){
				$this->error('验证码错误');
			}
			//保存新密码
			$result = $this->userService()->changeUserPwdByMobile($sms['phone'], $post['password']);
			if($result === false){
				$this->error('密码修改失败');
			}
			
			$mobile = $sms['phone'];
			$password = $post['password'];
			$userService = $this->userService();
			$user = $userService->getUserInfoWithMobile($mobile);
			if(!$userService->userPwdChk($user, $password)){
				$this->error('用户名或密码错误');
			}
			
			//登录赠送积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			if(date('Ymd') != date('Ymd',$user['last_login'])){
				$params = array(
						'user_id'=>$user['user_id'],
						'point'=>$point_config['login']['fval'],
						'type'=>PointLogic::PointTypeLogin,
						//'ref_user_id'=>$user['user_id'],
						'ref_id'=>$user['user_id']
				);
				$result = $this->pointService()->addUserPoint($params);
				if($result === false){
					$this->error('赠送积分失败');
				}
			}
				
			//更新登录时间和IP
			$data = array(
					'last_login'=>NOW_TIME,
					'last_ip'=>get_client_ip()
			);
			if (!$userService->modify($user, $data)) {
				$this->error('登录失败');
			}
				
			session('userid', $user['user_id']);
			
			$this->success('密码修改成功', U('user/index/index'));
		}
		$this->display();
	}

	public function SetLoginAction(){
        $result = 0;
		if(IS_POST){
			$post = I('post.');
			$uid = $post['uid'];
            //echo '-'.$uid;
            //echo '-'.$_COOKIE['PHPSESSID'].'-';
            //$apiSecret = $_SERVER['Jy-Secret'];
            $headers = getallheaders();
            $apiSecret = $headers['Jy-Secret'];
            //echo $apiSecret;
            if($apiSecret == '9fCzHpZ7HYps4u') {
                $userService = $this->userService();
                $user = $userService->getUserInfoById($uid);
                session('userid', $uid);
                //$result = 1;
                print_r(json_encode(array(
                    'errCode' => 0,
                    'data' => array(
                        'mobile' => $user['mobile']
                    )
                )));
                return;
            }
            else {
                //$result = -1;
                print_r(json_encode(array(
                    'errCode' => 1011,
                    'errMsg' => 'auth error'
                )));
                return;
            }
		}
        echo $result;
	}
	
	//获取城市列表
	public function get_regionAction(){
		$type=I('type')?I('type'):I('get.type');
		$code=I('code')?I('code'):I('get.code');
		$str='';
		$list=$this->regionService()->getChildList($code);
		
		if($type=='city_show'){
			$str="<option value=''>市</option>";
		}elseif($type=='district_show'){
			$str="<option value=''>区/县</option>";
		}else{
			$str="<option value=''>省</option>";
		}
		
		foreach($list as $key=>$val){
			$str.="<option value='{$key}'>{$val}</option>";
		}
		
		$this->ajaxReturn(array('html'=>$str));
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
}
