<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;

class MessageController extends FController {
	public function verifyAction(){
		$post = I('post.');
		$admin = $this->adminService()->getAdminInfoWithMobile($post['phone']);
		$result = $this->smsService()->sendSms($admin, $post['phone'], $post['verify_type']);
		$data = array(
			'Message'=>'短信发送成功',
			'SmsId'=>$result['sms_id'],
		);
		$this->jsonReturn($data);
	}
	
	protected function adminService(){
		return D('Admin', 'Service');
	}
	
	protected function smsService(){
		return D('Sms', 'Service');
	}
}