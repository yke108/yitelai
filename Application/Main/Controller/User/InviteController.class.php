<?php
namespace Main\Controller\User;
use Main\Controller\MainController;

class InviteController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
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
		
		$reg_url = U('index/site/reg', array('uid'=>$this->user['user_id']));
		$url = DK_DOMAIN.str_replace('index.php', 'wap/index.php', $reg_url);
		$this->assign('url', $url);
		
		$this->display();
    }
	
	private function qrcodeService(){
		return D('UserQrcode', 'Service');
	}
}