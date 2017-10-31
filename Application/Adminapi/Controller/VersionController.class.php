<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;
use Common\Basic\SystemConst;

class VersionController extends FController {
	public function androidAction(){
		$post = I('post.');
		$info = $this->configService()->findConfigs(SystemConst::ConfAndroidVersion);
		$data = array(
			'Version'=>$info['version'],
			'Message'=>$info['message'],
			'Url'=>$info['url'],
			'VersionForce'=>$info['version_force'],
		);
		$this->jsonReturn($data);
	}
	
	protected function configService(){
		return D('Config', 'Service');
	}
}