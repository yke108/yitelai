<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class InviteController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$qrcodeService = $this->qrcodeService();
		$qrcode = $qrcodeService->findQrcodeOfUser($this->user);
		if(empty($qrcode) || empty($qrcode['qrcode_url']) 
		|| ($qrcode['expire_time'] > 0 && $qrcode['expire_time'] < NOW_TIME)){
			try{
				$qrcode = $qrcodeService->createQrcode($this->user, $qrcode);
			} catch (\Exception $e) {
				$this->assign('message', $e->getMessage());
			}
		}
		$this->assign('qrcode', $qrcode);
		
		$url = DK_DOMAIN.U('index/site/reg', array('uid'=>$this->user['user_id']));
		$this->assign('url', $url);
		
		$this->display();
    }
	
	private function qrcodeService(){
		return D('UserQrcode', 'Service');
	}
}