<?php
namespace Home\Controller\Feedback;
use Home\Controller\BaseController;
use Common\Basic\Status;

class AdminController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*客服管理*/
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'sys_id'=>session('sys_id'),
    			'map'=>array('admin_type'=>array('in', array(Status::AdminTypePreSale, Status::AdminTypeAfterSale, Status::AdminTypeVisit, Status::AdminTypeComplaint))),
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$params['org_id'] = session('distributor_id');
    	}
    	if (I('admin_type')) {
    		$params['admin_type'] = I('admin_type');
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['org_id'] = I('store_id');
    	}
    	$result = $this->adminService()->adminPagerList($params);
    	foreach ($result['list'] as $k => $v) {
    		$result['list'][$k]['avatar'] = $v['avatar'] ? picurl($v['avatar'], 'b90') : '';
    	}
    	$this->assign('list', $result['list']);
    	
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
    	
    	//客服类型
    	$this->assign('admin_type_list', Status::$adminTypeList);
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '所有客服');
    	$this->display();
    }
    
    public function infoAction($admin_id = 0) { /*NoPurview*/
    	$info = $this->adminService()->getAdmin($admin_id);
    	if(empty($info) || $info['is_admin'] > 0) $this->error('客服不存在');
    	$info['avatar'] = $info['avatar'] ? picurl($info['avatar'], 'b90') : '';
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '客服详情');
    	$this->display();
    }
    
    public function addAction() { /*NoPurview*/
    	if (IS_POST) {
    		$params = I('post.');
    		$params['username'] = $params['mobile'];
    		$params['sys_id'] = session('sys_id');
    		$params['org_id'] = session('distributor_id') ? session('distributor_id') : 1;
    		$params['role_id'] = session('distributor_id') ? 53 : 58; //角色默认为客服
    		if ($params['admin_id']) {
    			$admin_info = $this->adminService()->getAdmin($params['admin_id']);
    			$params['username'] = $admin_info['username'];
    			$params['name'] = $admin_info['admin_name'];
    			$params['mobile'] = $admin_info['mobile'];
    		}
    		try {
    			$this->adminService()->adminCreateOrModify($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('feedback/index'));
    	}
    	
    	//人员
    	$map = array(
    			'sys_id'=>session('sys_id'),
    			'org_id'=>session('distributor_id') ? session('distributor_id') : Status::SysIdPlatform,
    			'is_admin'=>0,
    			'admin_type'=>array('not in', array(Status::AdminTypePreSale, Status::AdminTypeAfterSale, Status::AdminTypeVisit, Status::AdminTypeComplaint)), //排除已经是客服的管理员
    			'role_id'=>array('neq', 54), //排除店长
    	);
    	$admin_list = $this->adminService()->adminAllList($map);
    	$this->assign('admin_list', $admin_list);
    	
    	//客服类型
    	$this->assign('admin_type_list', Status::$adminTypeList);
    	
    	$this->assign('page_title', '添加客服');
    	$this->display();
    }
    
    private function distributorService(){
    	return D('Distributor','Service');
    }
}