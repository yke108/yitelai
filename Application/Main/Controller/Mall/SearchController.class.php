<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Payment\WeixinPay\AppPay;
use Common\Basic\Status;

class SearchController extends MainController {	
	public function _initialize(){
		parent::_initialize();
    }
	
	function indexAction(){
		$search_type = in_array(I('search_type'), array(1,2,3,4,5)) ? I('search_type') : 1;
		$keyword = I('keyword');
		switch ($search_type) {
			case 1: $url = U('mall/category/index', array('search_type'=>$search_type, 'keyword'=>$keyword));break;
			case 2: $url = U('mall/store/index', array('search_type'=>$search_type, 'keyword'=>$keyword));break;
			case 3: $url = U('story/index/list', array('search_type'=>$search_type, 'keyword'=>$keyword));break;
			case 4: $url = U('mall/brand/index', array('search_type'=>$search_type, 'keyword'=>$keyword));break;
			case 5: $url = U('design/index/case_list', array('search_type'=>$search_type, 'keyword'=>$keyword));break;
		}
		header('Location:'.$url);
	}
}