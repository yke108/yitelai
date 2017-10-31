<?php
namespace Admin\Controller\Message;
use Admin\Controller\FController;
use Common\Basic\Pager;

class SmstemplateController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'message',
			'ac'=>'message_smstemplate_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$params = array(
    			'page'=>$get['p'],
    	);
    	$smsService = $this->SmsService();
    	$result = $smsService->smsTemplatePagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	$this->display();
    }
	
	public function editAction($id = 0){
		$this->purviewCheck('smstemplate_edit');
		$smsService = $this->smsService();
		$template = $smsService->getSmsTemplate($id);
		if (empty($template)) $this->error('模板不存在');
		if(IS_POST){
			$post = I('post.');
			$params = $post;
			$params['template_id'] = $id;
			try {
				$smsService->smsTemplateModify($params);
				$this->success('修改成功');
			} catch (CsException $e) {
				$this->error($e->getMessage());
			}
		}
		$this->assign('info', $template);
		$this->display();
	}
	
	protected function smsService(){
		return D('Sms', 'Service');
	}
}