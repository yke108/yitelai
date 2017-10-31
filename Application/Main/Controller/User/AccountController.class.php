<?php
namespace Main\Controller\User;
use Main\Controller\MainController;

class AccountController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		$this->display();
    }
	
    public function chpwdAction(){
		if(IS_POST){
			$post = I('post.');
			
			// 检查验证码
			if(!check_verify($post['code'])){
				$this->error('验证码不正确');
			}
			
			if(!$this->userService()->userPwdChk($this->user, $post['old_password'])){
				$this->error('旧密码不正确');
			}
			
			if(strlen(trim($post['password'])) < 6 
			|| $post['password'] != $post['password2']){
				$this->error('新密码不正确');
			}
			//保存新密码
			$result = $this->userService()->changeUserPwd($this->user, $post['password']);
			if($result === false){
				$this->error('密码修改失败');
			}
			$this->success('密码修改成功', U('index'));
		}
		$this->display();
    }
	
    public function chtradeAction(){
		$get = I('get.');
		$userService = $this->userService();
		if(empty($get['vc']) || !$userService->verifyCodeForTradePwdCheck($this->user, $get['vc'])){
			$this->display('real');
		}
		if(IS_POST){
			$post = I('post.');
			if($post['password'] != $post['password2']){
				$this->error('密码不正确');
			}
			//保存新密码
			$result = $userService->changeTradePwd($this->user, $post['password']);
			if($result === false){
				$this->error('操作失败');
			}
			$msg = empty($this->user['trade_pwd']) ? '支付密码设置成功' : '支付密码修改成功';
			$this->error($msg, U('index'));
		}
		$this->assign('get', I('get.'));
		$this->display();
    }
	
    public function chpaypwdAction(){
		if(IS_POST){
			$userService = $this->userService();
			$post = I('post.');
			if(strlen(trim($post['password'])) < 6 
			|| $post['password'] != $post['password2']){
				$this->error('密码不正确');
			}
			//读取数据
			$sms = $this->smsService()->getCheckedRecord($post['ssid'], $post['code']);
			if(empty($sms)){
				$this->error('验证码错误');
			}
			//保存新密码
			$result = $userService->changeTradePwd($this->user, $post['password'], $sms['phone']);
			if($result === false){
				$this->error('操作失败');
			}
			$msg = empty($this->user['trade_pwd']) ? '支付密码设置成功' : '支付密码修改成功';
			$this->success($msg, '', array('go_back'=>1));
		}
		$this->display();
    }
	
	public function realAction(){
		if(IS_POST){
			$post = I('post.');
			$data = array(
				'business_no'=>$post['ssid'],
				'sms_code'=>$post['code'],
			);
			try{
				$result = $this->yeepayEvent()->realVerifyConfirm($this->user, $data);
			} catch (\Exception $e){
				$this->error($e->getMessage());
			}
			$this->display('real_ok');
		}
		$this->display();
	}
	
	public function verifiedAction(){
		if($this->user['id_verified'] < 1){
			$this->display('real');
		}
		$this->display();
	}
	
	public function code2Action(){
		if(!IS_POST) $this->error('操作失败');
		$post = I('post.');
		$data = array(
			'name'=>$post['real_name'],
			'id_no'=>$post['id_no'],
			'card_no'=>$post['bank_card'],
			'phone'=>$post['phone'],
		);
		try{
			if($this->user['id_verified'] < 1){
				$result = $this->yeepayEvent()->realVerify($this->user, $data);
			} else {
				$result = $this->yeepayEvent()->bindCardReset($this->user, $data);
			}
		} catch (\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('验证码发送成功', '', array('ssid'=>$result['BusinessNo']));
	}
	
	public function codeAction(){
		$post = I('post.');
		$phone = trim($post['phone']);
		$type = intval(trim($post['type']));
		if($type < 1) $this->error('发送失败');
		$this->user['mobile'] != $phone && $this->error('手机号码不正确');
		$result = $this->smsService()->sendSms($this->user, $phone, $type);
		if(!is_array($result)){
			$this->error($result);
		}
		$this->success('验证码发送成功', '', array('ssid'=>$result['sms_id']));
	}
	
	public function code3Action(){
		$post = I('post.');
		$phone = trim($post['phone']);
		$type = intval(trim($post['type']));
		if($type < 1) $this->error('发送失败');
		if(empty($this->user['mobile'])){
			if($this->userService()->getUserInfoWithMobile($phone)){
				$this->error('手机号码已其他账号使用');
			}
		} else {
			$this->user['mobile'] != $phone && $this->error('请输入绑定的手机号码');
		}
		$result = $this->smsService()->sendSms($this->user, $phone, $type);
		if(!is_array($result)){
			$this->error($result);
		}
		$this->success('验证码发送成功', '', array('ssid'=>$result['sms_id']));
	}
	
	/**
	 *
	 * 验证码生成
	 */
	public function verify_cAction(){
		ob_clean();
		ob_end_clean(); 
		$Verify = new \Think\Verify();
		$Verify->fontSize = 12;
		$Verify->length   = 4;
		$Verify->useNoise = false;
		//$Verify->codeSet = '4846';
		$Verify->imageW = 90;
		$Verify->imageH = 26;
		$Verify->expire = 600;
		$Verify->entry();
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
	
	private function yeepayEvent(){
		return D('Yeepay', 'Event');
	}
}