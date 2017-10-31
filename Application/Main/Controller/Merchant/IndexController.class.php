<?php
namespace Main\Controller\Merchant;
use Main\Controller\MainController;
use Common\Basic\Status;

class IndexController extends MainController {
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$map = array(
    			'user_id'=>$this->user['user_id'],
    			//'status'=>array('in', array(Status::MerchantStatusOn, Status::MerchantStatusPass))
    	);
    	$merchant_info = $this->merchantService()->searchInfo($map);
    	if ($merchant_info) {
    		$url = 'merchant/apply/step'.$merchant_info['step'];
    		$this->redirect($url);
    	}
    	
    	$map = array('page_type'=>Status::PageTypeMerchant);
    	$pages = $this->pageService()->infoAllList($map);
    	$this->assign('pages', $pages);
    	
    	$this->display();
    }
    
    private function pageService(){
    	return D('Page', 'Service');
    }
    
    private function merchantService(){
    	return D('Merchant', 'Service');
    }
}