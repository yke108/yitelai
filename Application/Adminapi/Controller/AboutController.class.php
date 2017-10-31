<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;
use Common\Basic\SystemConst;

class AboutController extends FController {
	public function indexAction(){
		$post = I('post.');
		$info = $this->configService()->findConfigs(SystemConst::ConfAppAbout);
		$data = array(
			'CompanyInfo'=>$info['company_info'],
			'CompanyName'=>$info['company_name'],
		);
		$this->jsonReturn($data);
	}
	
	protected function configService(){
		return D('Config', 'Service');
	}
}