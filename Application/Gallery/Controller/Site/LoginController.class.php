<?php
namespace Gallery\Controller\Site;
use Gallery\Controller\FController;
use Think\Controller;

class LoginController extends FController {
	public function _initialize(){
		$this->settingForAll();
		layout(false);
    }
	
    public function indexAction(){
		if(IS_POST){
			$post = I('post.');
			$params = array(
				'username'=>strtolower(trim($post['username'])),
				'password'=>$post['password'],
				'sys_id'=>$this->sys_id,
			);
			try {
				$admin = $this->adminService()->login($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->sessionSet($admin);
			$this->redirect('material/index/index');
		}
		$this->assign('info', $_POST);
		$this->display();
    }
	
	public function logoutAction(){
		session(null);
		cookie(null);
		header("Location:".U('site/login'));
	}
	
	public function getForgetCodeAction(){
		$post = I('post.');
	
		$phone = trim($post['phone']);
		$map = array('mobile'=>$phone, 'sys_id'=>$this->sys_id);
		try {
			$user = $this->adminService()->findAdmin($map);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	
		$type = 4;
		try {
			$result = $this->smsService()->sendSms($user, $phone, $type);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	
		//过滤
		$filter = array('13912345678');
		if (in_array($phone, $filter)) {
			$this->success('验证码发送成功', '', $result);
		}
	
		$this->success('验证码发送成功', '', array('sms_id'=>$result['sms_id']));
	}
	
	public function forgetAction(){
		if (IS_POST) {
			$post = I('post.');
			if(strlen(trim($post['password'])) < 6 || $post['password'] != $post['password2']){
				$this->error('密码不正确');
			}
			//读取数据
			$type = 4;
			$sms = $this->smsService()->getCheckedRecord($post['sms_id'], $post['code'], $type);
			if(empty($sms)){
				$this->error('验证码错误');
			}
			//保存新密码
			$result = $this->adminService()->adminChangePwdByMobile($sms['phone'], $post['password'], $this->sys_id);
			if($result === false){
				$this->error('密码修改失败');
			}
			$this->success('密码修改成功', U('index'));
		}
		$this->display();
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
}