<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;
use Common\Basic\Tool;
use Common\Basic\SystemConst;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() /*客户列表*/
    {
    	//用户列表
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$params['distributor_id'] = session('distributor_id');
    	}
    	//店铺管理员
    	if (session('sys_id') == 2 && session('is_admin') == 0) {
    		$params['admin_id'] = session('uid');
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('from')) {
    		$params['from'] = I('from');
    	}
    	if (I('brand_id')) {
    		$params['brand_id'] = I('brand_id');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
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
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		if (!empty($distributor_list)) {
    			foreach ($distributor_list as $v) {
    				$city_code[] = intval($v['region_code'] / 100) * 100;
    			}
    			$city_list = M('region')->where(array('region_code'=>array('in', $city_code)))->select();
    			$this->assign('city_list', $city_list);
    		}
    	}
    	
    	//来源筛选
    	$this->assign('from_list', Status::$fromList);
    	
    	//品牌筛选
    	$map = array();
    	if (session('distributor_id')) {
    		$distributor_info = $this->distributorService()->getInfo(session('distributor_id'));
    		if ($distributor_info['brand_ids']) {
    			$brand_ids = trim($distributor_info['brand_ids'], ',');
    			$map['brand_id'] = array('in', $brand_ids);
    		}else {
    			$map['brand_id'] = 0;
    		}
    	}
    	$brand_list = $this->goodsBrandService()->getAllList($map);
    	$this->assign('brand_list', $brand_list);
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '客户列表');
		$this->display();
    }
    
    public function infoAction($user_id = 0) /*客户详情*/
    {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('客户不存在');
    	$this->assign('info', $info);
    	
    	if ($info['distributor_id']) {
    		$distributor_info = $this->distributorService()->getInfo($info['distributor_id']);
    		$this->assign('distributor_info', $distributor_info);
    	}
    	
    	//来源
    	$this->assign('from_list', Status::$fromList);
    	
    	$this->assign('page_title', '客户详情');
    	$this->display();
    }
    
    public function addAction() /*添加客户*/
    {
    	if (IS_POST) {
    		$post = I('post.');
    		$params = $this->checkAddData($post);
    		try {
    			$this->userService()->userCreateOrModify($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	$this->assign('page_title', '添加客户');
    	$this->display();
    }
    
    public function editAction($user_id = 0) /*编辑客户资料*/
    {
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('客户不存在');
    	
    	if (IS_POST) {
    		$post = I('post.');
    		$params = $this->checkEditData($post);
    		try {
    			$this->userService()->userCreateOrModify($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('编辑成功', U('info', array('user_id'=>$user_id)));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '编辑客户资料');
    	$this->display();
    }
    
    public function moveAction($user_id = 0) /*转移客户*/
    {
    	if (session('sys_id') == SystemConst::SysAdmin){
    		$this->movedByPlatform($user_id);
    	} else {
    		$this->movedByDistributor($user_id);
    	}
    }
    
    private function movedByPlatform($user_id){
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['distributor_id'])) $this->error('请选择指派的店铺');
    		if (empty($post['designate_remark'])) $this->error('请填写备注信息');
    		$user = $this->userService()->getUserInfo($user_id);
    		if (empty($user)) $this->error('用户不存在');
    		 
    		$data = array(
    			'distributor_id'=>$post['distributor_id'],
    			'admin_id'=>0,
    			'designate_remark'=>$post['designate_remark'],
    		);
    		try {
    			$this->userService()->modify($user, $data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('转移成功', U('index'));
    	}
    
    	$get = I('get.');
    	$this->assign('get', $get);
    
    	//店铺列表
    	$map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    
    	$this->assign('page_title', '客户指派');
    	$this->display('move');
    }
    
    private function movedByDistributor($user_id){
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['designate_remark'])) $this->error('请填写备注信息');
    		if ($post['admin_id'] < 1) $this->error('未指定跟进人员');
    		$user = $this->userService()->getUserInfo($post['user_id']);
    		if (empty($user)){
    			$this->error('用户不存在');
    		}
    		if ($user['distributor_id'] > 0
    				&& $user['distributor_id'] != session('distributor_id')){
    					$this->error('用户不存在');
    		}
    		$admin = $this->adminService()->getAdmin($post['admin_id']);
    		if (empty($admin) || $admin['org_id'] != session('distributor_id')){
    			$this->error('跟进人不存在');
    		}
    		$data = array(
    			'admin_id'=>$admin['admin_id'],
    			'grab_type'=>Status::GrabTypeTransfer,
    			'grab_time'=>NOW_TIME,
    		);
    		$user['distributor_id'] < 1 && $data['distributor_id'] = session('distributor_id');
    		try {
    			$this->userService()->modify($user, $data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('转移成功', U('index'));
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
    	$this->display('move2');
    }
    
    public function leaderAction() /*查看全部用户*/ { /*仅用于权限管理*/ }
    
    private function checkAddData($post) {
    	if (empty($post['real_name'])) $this->error('姓名不能为空');
    	if (empty($post['mobile'])) $this->error('手机号码不能为空');
    	if (!Tool::is_phone($post['mobile'])) $this->error('手机号码格式不正确');
    	if (!empty($post['password']) && $post['password'] != $post['repassword']) $this->error('再次输入的密码不相同');
    	
    	$map = array('mobile'=>$post['mobile']);
    	$user_info = $this->userService()->getUser($map);
    	if ($user_info) $this->error('用户已存在');
    	
    	$params = array(
    			'real_name'=>$post['real_name'],
    			'mobile'=>$post['mobile'],
    			'province'=>$post['province'],
    			'city'=>$post['city'],
    			'region_code'=>$post['region_code'],
    			'nick_name'=>$post['nick_name'],
    			'sex'=>$post['sex'],
    			'distributor_id'=>session('distributor_id'),
    			'admin_id'=>session('uid'),
    			'from'=>Status::FromAndroid,
    			'from_url'=>__SELF__,
    			'reg_time'=>NOW_TIME,
    	);
    	if ($post['password']) {
    		$params['password'] = $this->userService()->userPwd($post['password']);
    	}
    	
    	$images = createBase64Image($post['user_img']);
    	if (!empty($images)) {
    		$params['user_img'] = $images[0];
    	}
    	
    	return $params;
    }
    
    private function checkEditData($post) {
    	$user_info = $this->userService()->getUserInfo($post['user_id']);
    	if (empty($user_info)) $this->error('用户不存在');
    	if ($user_info['distributor_id'] != session('distributor_id') && session('sys_id') == 2) $this->error('权限不够，无法操作');
    	if (empty($post['real_name'])) $this->error('客户名称不能为空');
    	if (empty($post['mobile'])) $this->error('客户电话不能为空');
    	if (!Tool::is_phone($post['mobile'])) $this->error('客户电话格式不正确');
    	
    	$params = array(
    			'user_id'=>$post['user_id'],
    			//'user_img'=>$post['user_img'],
    			'real_name'=>$post['real_name'],
    			'mobile'=>$post['mobile'],
    			'province'=>$post['province'],
    			'city'=>$post['city'],
    			'region_code'=>$post['region_code'],
    			'nick_name'=>$post['nick_name'],
    			'sex'=>$post['sex'],
    	);
    	
    	$images = createBase64Image($post['user_img']);
    	if (!empty($images)) {
    		$params['user_img'] = $images[0];
    	}
    	
    	return $params;
    }
    
    public function search_cityAction($keyword = '') { /*NoPurview*/
    	$map = array('status'=>Status::DistributorStatusSuccess);
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
    
    public function search_brandAction($keyword = '') { /*NoPurview*/
    	$map = array();
    	if ($keyword) {
    		$map['brand_name'] = array('like', '%'.trim($keyword).'%');
    	}
    	$brand_list = $this->goodsBrandService()->getAllList($map);
    	
    	$this->assign('keyword', $keyword);
    	$this->assign('brand_list', $brand_list);
    	$html = $this->renderPartial('_brand_list');
    	$this->ajaxReturn(array('html'=>$html));
    }
    
    public function search_storeAction($keyword = '') { /*NoPurview*/
    	$map = array('status'=>Status::DistributorStatusNormal);
    	if ($keyword) {
    		$map['distributor_name'] = array('like', '%'.trim($keyword).'%');
    	}
    	$distributor_list = $this->distributorService()->getAllList($map);
    	 
    	$this->assign('keyword', $keyword);
    	$this->assign('distributor_list', $distributor_list);
    	$html = $this->renderPartial('_distributor_list');
    	$this->ajaxReturn(array('html'=>$html));
    }
    
    private function distributorService() {
    	return D('Distributor', 'Service');
    }
    
    private function goodsBrandService() {
    	return D('GoodsBrand', 'Service');
    }
}