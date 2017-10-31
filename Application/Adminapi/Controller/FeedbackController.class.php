<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;
use Common\Basic\CsException;

class FeedbackController extends FController {
	public function appAction(){
		$post = I('post.');
		if ($post['admin_id'] < 1) throw new CsException('未登录', 11);
		$params = array(
			'admin_id'=>$post['admin_id'],
			'content'=>trim($post['content']),
			'pictures'=>$post['pictures'],
			'client'=>$post['client'],
			'about'=>$post['about'],
		);
		$this->feedbackService()->feedbackAppCreate($params);
		$data = array(
			'Message'=>'添加成功',
		);
		$this->jsonReturn($data);
	}
	
	protected function feedbackService(){
		return D('Feedback', 'Service');
	}
}