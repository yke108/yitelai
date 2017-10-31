<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class SalaryController extends BaseController {
	protected $d_sys_id = 2;
	protected $d_org_id = 2;
	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /* 人员薪酬结算 */
    	//人员
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$params['distributor_id'] = session('distributor_id');
    	}
    	if (I('department_id')) {
    		$params['department_id'] = I('department_id');
    	}
    	if (I('keyword')) {
    		$params['nick_name'] = I('keyword');
    	}
    	if (session('status')) {
    		$params['status'] = session('status');
    	}
    	if (session('city')) {
    		$params['city'] = session('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$wageList = $this->adminService()->appWageService($params);
    	$this->assign("wageList", $wageList['list']);
    	$this->assign("count", $wageList['count']);
    	$this->assign("year", $wageList['year']);
    	
    	if (IS_AJAX) {
    		if(empty($wageList)){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$get['type'] = $get['type'] ? $get['type'] : 1;
    	$this->assign('get', $get);
    	
    	//部门
    	$params = array(
    			'sys_id'=>$this->d_sys_id,
    			'org_id'=>$this->d_org_id,
    	);
    	$department_list = $this->adminService()->departmentList($params);
    	$this->assign('department_list', $department_list);
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '人员薪酬结算');
		$this->display();
    }
    
    public function infoAction($user_id = 0) {
    	$admin_id = I('admin_id');
    	$admin_field = array('admin_id', 'mobile', 'admin_name','avatar', 'is_admin', 'role_id', 'sys_id', 'org_id');
    	$adminFind = $this->adminService()->adminFindService(array('admin_id' => $admin_id), $admin_field);
    	if ($adminFind['avatar']) {
    		$adminFind['avatar'] = domain_name_url . '/upload/' . $adminFind['avatar'];
    	} else {
    		$adminFind['avatar'] = domain_name_url . '/public/main/images/user_default_img.jpg';
    	}
    	$adminRoleFind = D('AdminRole')->where(array('role_id' => $adminFind['role_id']))->field(array('role_name'))->find();
    	$adminFind['role_name'] = $adminRoleFind['role_name'];
    	$distributorInfoFind = $this->distributorInfoService()->getServiceFind(array('distributor_id' => $adminFind['org_id']));
    	$this->assign("adminFind", $adminFind);
    	$this->assign("admin_id", $admin_id);
    	$this->assign("distributorInfoFind", $distributorInfoFind);
    	
    	$params = array(
    			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'admin_id' => $admin_id,
    			'pagesize' => $this->pagesize,
    	);
    	$wageList = $this->adminService()->appWageService($params);
    	$this->assign("wageList", $wageList['list']);
    	$this->assign("count", $wageList['count']);
    	$this->assign("year", $wageList['year']);
    	
    	if (IS_AJAX) {
    		if (empty($wageList['list'])) {
    			$clist = '';
    		} else {
    			$clist = $this->renderPartial('_load_detail');
    		}
    		$this->ajaxReturn(array('html' => $clist, 'p' => $params['page'] + 1));
    	}
    	
    	$this->assign('page_title', '人员薪酬结算-详情');
    	$this->display();
    }
    
    private function cashApplyService() {
    	return D('CashApply', 'Service');
    }
    
    private function distributorService() {
    	return D('Distributor', 'Service');
    }
    
    private function distributorInfoService()
    {
    	return D('DistributorInfo', 'Service');
    }
}