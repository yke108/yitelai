<?php
namespace Wap\Controller\Mall;
use Wap\Controller\WapController;
use Common\Basic\Pager;

class GiftController extends WapController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//导航栏
    	$params = array(
    			'is_show'=>1,
    			'type'=>1,
    			'distributor_id'=>0
    	);
    	if ($get['dis_id']) {
    		$params['store_id'] = $get['dis_id'];
    	}
    	$nav = $this->navService()->getPagerList($params);
    	$this->assign('nav_list', $nav['list']);
    	
    	$this->assign('page_title', '大牌明星产品区-无线');
		$this->display();
    }
}