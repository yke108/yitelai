<?php
namespace Home\Controller\Platform;
use Home\Controller\BaseController;

class DistrictController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		
		$user_type = \Common\Basic\User::$user_type;
		$this->assign('user_type', $user_type);
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*区域经理*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'user_type'=>4,
    	);
    	$result = $this->UserService()->userPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$this->assign('page_title', '区域经理');
		$this->display();
    }
    
    public function infoAction($user_id = 0) {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('用户不存在');
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '区域经理-详情');
    	$this->display();
    }
    
    public function brandAction($user_id = 0) {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('用户不存在');
    	$this->assign('info', $info);
    	
    	//品牌
    	$map = array('brand_id'=>array('in', $info['brand_ids']));
    	$brand_list = $this->goodsBrandService()->getAllList($map);
    	$this->assign('brand_list', $brand_list);
    	
    	$this->assign('page_title', '负责品牌');
    	$this->display();
    }
    
    public function districtAction($user_id = 0) {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('用户不存在');
    	$this->assign('info', $info);
    	
    	//店铺
    	$map = array('status'=>Status::DistributorStatusNormal, 'distributor_id'=>array('in', $info['distributor_ids']));
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->assign('page_title', '负责区域');
    	$this->display();
    }
    
    public function storeAction($user_id = 0) {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('用户不存在');
    	$this->assign('info', $info);
    	
    	//店铺
    	$map = array('status'=>Status::DistributorStatusNormal, 'distributor_id'=>array('in', $info['distributor_ids']));
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->assign('page_title', '负责店铺');
    	$this->display();
    }
    
    private function goodsBrandService(){
    	return D('GoodsBrand', 'Service');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
}