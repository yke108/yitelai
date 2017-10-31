<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;
use Common\Basic\CsException;
use Common\Basic\SystemConst;

class PoolController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() /*客户池*/
    {
    	//用户列表
    	$stl = [0];
    	//店铺
    	session('distributor_id') > 0 && $stl[] = session('distributor_id');
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'store_id'=>['in', $stl],
    			'admin_id'=>0,
    	);
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('from')) {
    		$params['from'] = I('from');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	if (I('demand')) {
    		$params['demand'] = I('demand');
    	}
    	//只看到店铺所在城市的客户
    	if (session('distributor_id')) {
    		$distributor_info = $this->distributorService()->getInfo(session('distributor_id'));
    		if ($distributor_info['region_code']) {
    			$city_name = $this->regionService()->getDistrictCityName($distributor_info['region_code']);
    			$city_name = str_replace('市', '', $city_name);
    			$map['city'] = array('like', '%'.$city_name.'%');
    		}else {
    			$map['city'] = '';
    		}
    	}
    	$params['map'] = $map;
    	$result = $this->userService()->userPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//城市筛选
    	$distributor_list = $this->distributorService()->getAllList();
    	if (!empty($distributor_list)) {
    		foreach ($distributor_list as $v) {
    			$city_code[] = intval($v['region_code'] / 100) * 100;
    		}
    		$city_list = M('region')->where(array('region_code'=>array('in', $city_code)))->select();
    		$this->assign('city_list', $city_list);
    	}
    	
    	//来源筛选
    	$this->assign('from_list', Status::$fromList);
    	
    	$this->assign('page_title', '客户池');
		$this->display();
    }
    
    public function infoBefore(){
    	$this->purviewCheck('index');
    }
    
    public function infoAction($user_id = 0) /*NoPurview*/
    {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('客户不存在');
    	$this->assign('info', $info);
    	
    	//来源
    	$this->assign('from_list', Status::$fromList);
    	
    	$this->assign('page_title', '客户池详情');
    	$this->display();
    }
    
    public function grabAction($user_id = 0) /*抢客户*/
    {
    	$params = array(
    			'user_id'=>$user_id,
    			'distributor_id'=>session('distributor_id'),
    			'admin_id'=>session('uid'),
    	);
    	try {
    		$this->userService()->grabUser($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('成功抢得客户');
    }
    
    public function designateAction($user_id = 0) /*指派客户*/
    {
    	if (session('sys_id') == SystemConst::SysAdmin){
    		$this->designateByPlatform($user_id);
    	} else {
    		$this->designateByDistributor($user_id);
    	}
    }
    
    private function designateByPlatform($user_id){
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['distributor_id'])) $this->error('请选择指派的店铺');
    		if (empty($post['demand_id'])) $this->error('请选择客户需求');
    		if (empty($post['designate_remark'])) $this->error('请填写备注信息');
    		$user = $this->userService()->getUserInfo($user_id);
    		if (empty($user)) $this->error('用户不存在');
    		
    		$data = array(
    			'distributor_id'=>$post['distributor_id'],
				'demand_id'=>$post['demand_id'],
    			'designate_remark'=>$post['designate_remark'],
    		);
    		try {
    			$this->userService()->modify($user, $data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('指派成功', U('index'));
    	}
    	 
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	//店铺列表
    	/* $map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list); */
    	
    	//店铺
    	$distributor_info = $this->distributorService()->getInfo($get['store_id']);
    	$this->assign('distributor', $distributor_info);
    	
    	//客户需求
    	$map = array('is_show'=>1);
    	$demand_list = $this->userDemandService()->infoAllList($map);
    	$this->assign('demand_list', $demand_list);
    	
    	$this->assign('page_title', '客户指派');
    	$this->display();
    }
    
    private function designateByDistributor($user_id){
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['designate_remark'])) $this->error('请填写备注信息');
    		if ($post['admin_id'] < 1) $this->error('未提供具体跟进人员');
    		$user = $this->userService()->getUserInfo($post['user_id']);
    		if (empty($user)){
    			$this->error('用户不存在');
    		}
    		if ($user['distributor_id'] > 0
    				&& $user['distributor_id'] != session('distributor_id')){
    			$this->error('用户不存在');
    		}
    		if ($user['admin_id'] > 0){
    			$this->error('用户已有跟进人');
    		}
    		$admin = $this->adminService()->getAdmin($post['admin_id']);
    		if (empty($admin) || $admin['org_id'] != session('distributor_id')){
    			$this->error('跟进人不存在');
    		}
    		$data = array(
    			'admin_id'=>$admin['admin_id'],
    			'designate_remark'=>$post['designate_remark'],
    			'grab_type'=>Status::GrabTypePlatform,
    			'grab_time'=>NOW_TIME,
    		);
    		$user['distributor_id'] < 1 && $data['distributor_id'] = session('distributor_id');
    		try {
    			$this->userService()->modify($user, $data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('指派成功', U('index'));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	//店员列表
    	$map = [
    		'sys_id'=>SystemConst::SysDistributor,
    		'org_id'=>session('distributor_id'),
    		'oa_action_list'=>['like', '%,user/pool/grab,%'],
    	];
    	$admin_list = $this->adminService()->adminList($map);
    	$this->assign('list', $admin_list);
    	$this->assign('page_title', '客户指派');
    	$this->display('designate2');
    }
    
    public function search_cityAction($keyword = '') { /*NoPurview*/
    	$map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$city_list = array();
    	if (!empty($distributor_list)) {
    		foreach ($distributor_list as $v) {
    			$city_code[] = intval($v['region_code'] / 100) * 100;
    		}
    		$map = array('region_code'=>array('in', $city_code));
    		if ($keyword) {
    			$map['region_name'] = array('like', '%'.trim($keyword).'%');
    		}
    		$city_list = M('region')->where($map)->select();
    	}
    	
    	$this->assign('keyword', $keyword);
    	$this->assign('city_list', $city_list);
    	$html = $this->renderPartial('_city_list');
    	$this->ajaxReturn(array('html'=>$html));
    }
    
    public function distributor_listAction() { /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
				'status'=>Status::DistributorStatusNormal,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	$result = $this->distributorService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_distributor_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '选择店铺');
    	$this->display();
    }
    
    protected function distributorService(){
    	return D('Distributor', 'Service');
    }
    
    protected function userDemandService(){
    	return D('UserDemand', 'Service');
    }
    
    protected function regionService(){
    	return D('Region', 'Service');
    }
}