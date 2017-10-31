<?php
namespace Home\Controller\Platform;
use Home\Controller\BaseController;
use Common\Basic\Status;
class IndexController extends BaseController {	
	public function _initialize(){
        //$this->purviewCheck(false);
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }

    //www.yitelai.top/home/index.php/platform/index/index.html
    public function distributorlistAction(){/*NoPurview*/
        $region_code = I('region_code');
        $distributor_id = I('distributor_id');
        if (empty($region_code)) $this->error('发生致命错误');
        $where = array('region_code' => $region_code);
        $data = $this->distributorInfoService()->getServiceList($where);
        $_html = "";
        foreach ($data as $key => $val) {
            if ($val['distributor_id'] == $distributor_id) {
                $selected = "selected";
            } else {
                $selected = "";
            }
            $_html .= '<option ' . $selected . ' value="' . $val['distributor_id'] . '">' . $val['distributor_name'] . '</option>';
        }
        $this->ajaxReturn(array('info' => "返回数据成功", 'html' => $_html));
    }

    public function detailAction(){/*NoPurview*/
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
        $this->assign('page_title', '人员管理-详情');
        $this->display();
    }
	
    public function indexAction() { /*人员管理*/
        $this->assign('page_title', '人员管理');
        $this->assign('distributorListUrl', '/home/index.php/platform/index/distributorlist.html');
        $admin_id = session('uid');

        $admin_field = array('admin_id', 'admin_name', 'is_admin', 'role_id', 'sys_id', 'org_id');
        $adminFind = $this->adminService()->adminFindService(array('admin_id' => $admin_id), $admin_field);
        //平台
        $department_where = array();
        $personnel_role_string = '';
        $role_id_string = '';
        $department_list = array();
        if ($adminFind['sys_id'] == 1) {
            $distributor_where = array();
            $content = I('content');
            $province_city_area = I('province_city_area');//
            $distributor_id = I('distributor_id');
            $province = I('province');
            $city = I('city');
            $region_code = I('region_code');
            $this->assign("province_city_area", $province_city_area);
            $this->assign("province", $province);
            $this->assign("city", $city);
            $this->assign("region_code", $region_code);
            $this->assign("content", $content);
            $this->assign("distributor_id", $distributor_id);


            if($region_code){
                $_region_where = array('region_code' => $region_code);
                $distributor_data = $this->distributorInfoService()->getServiceList($_region_where);
                $this->assign("distributor_data", $distributor_data);
            }

            //判断搜索是不是以店铺搜索
            if ($distributor_id) {
                $distributor_where['distributor_id'] = $distributor_id;
            }
            $adminSoleFind = $this->adminService()->adminFindService(array('admin_id' => $admin_id), $admin_field);
            $_role_department_list = array();

            $AdminRole = D('AdminRole')->field(array('role_id', 'personnel_role'))->where(array('role_id' => $adminSoleFind['role_id']))->find();
            $role_id_string = $AdminRole['personnel_role'];
            if ($distributor_id) {
                $adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('sys_id' => 2, 'org_id' => 2))->select();
            } else {
                if ($adminFind['is_admin'] == Status::YES){
                    $adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('sys_id' => 1, 'org_id' => 1))->select();
                } else {
                    $adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('role_id' => array('in', $role_id_string)))->select();
                }
            }
            foreach ($adminRoleList as $k2 => $v2) {
                $_role_department_list[$v2['department_id']][] = $v2['role_id'];
            }
            if ($distributor_id) {
                $distributorInfoFind = $this->distributorInfoService()->getServiceFind($distributor_where);
                $department_where['sys_id'] = 2;
                $department_where['org_id'] = 2;
                $distributorAdminFind = $this->adminService()->adminFindService(array('org_id' => $distributorInfoFind['distributor_id']), $admin_field);
                $admin_id = $distributorAdminFind['admin_id'];
            } else {
                $department_where['sys_id'] = 1;
                $department_where['org_id'] = 1;
                if ($adminFind['is_admin'] != Status::YES) {
                    $_other_admin_department = '';
                    foreach ($_role_department_list as $k3 => $v3) {
                        $_other_admin_department .= $k3 . ',';
                    }
                    $department_where['personnel_role'] = $_other_admin_department;
                }
            }
            $department = $this->adminService()->adminDepartmentService($department_where);
            foreach ($department as $key => $val) {
                $_t = $val;
                $_t['key'] = $key;
                $personnel = array();
                $personnel_list = array();
                $role_where = array();
                if ($distributor_id) {
                    $role_where['sys_id'] = 2;
                    $role_where['org_id'] = $distributorAdminFind['org_id'];
                    $role_where['admin_name'] = array('like', '%' . $content . '%');
                } else {
                    $role_where['sys_id'] = 1;
                    $role_where['org_id'] = 1;
                }
                if ($_role_department_list[$val['department_id']]) {
                    $role_where[] = array('role_id' => array('in', implode(',', $_role_department_list[$val['department_id']])),);
                    $personnel = D('Admin')->field(array('admin_id', 'role_id', 'admin_name', 'username', 'avatar'))->where($role_where)->select();
                    foreach ($personnel as $k => $v) {
                        $_ts = $v;
                        $adminRoleFind = D('AdminRole')->where(array('role_id' => $v['role_id']))->field(array('role_name'))->find();
                        $_ts['role_name'] = $adminRoleFind['role_name'];
                        $_ts['detailUrl'] = U('Platform/index/detail', array('admin_id' => $v['admin_id']));
                        if ($v['avatar']) {
                            $_ts['avatar'] = domain_name_url . '/upload/' . $v['avatar'];
                        } else {
                            $_ts['avatar'] = domain_name_url . '/public/main/images/user_default_img.jpg';
                        }
                        $personnel_list[] = $_ts;
                    }
                }
                //var_dump(M()->getLastSql());
                $_t['role'] = implode(',', $_role_department_list[$val['department_id']]);
                $_t['personnel'] = $personnel_list;
                $department_list[] = $_t;
            }
            //是不是后台管理员：1 是;2 店铺
            $this->assign("is_system_admin" , 1);
        } else {
            //店铺
            $adminSoleFind = $this->adminService()->adminFindService(array('admin_id' => $admin_id), $admin_field);
            $department_where['sys_id'] = 2;
            $department_where['org_id'] = 2;
            $_role_department_list = array();
            $department = $this->adminService()->adminDepartmentService($department_where);
            if ($adminSoleFind['role_id']) {
                $AdminRole = D('AdminRole')->field(array('role_id', 'personnel_role'))->where(array('role_id' => $adminSoleFind['role_id']))->find();
                $role_id_string = $AdminRole['personnel_role'];
                $adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('role_id' => array('in', $role_id_string)))->select();
                foreach ($adminRoleList as $k2 => $v2) {
                    $_role_department_list[$v2['department_id']][] = $v2['role_id'];
                }
            }
            foreach ($department as $key => $val) {
                $_t = $val;
                $_t['key'] = $key;
                $personnel = array();
                $personnel_list = array();
                if ($role_id_string) {
                    $role_where = array();
                    $role_where['sys_id'] = 2;
                    if ($_role_department_list[$val['department_id']]) {
                        $role_where[] = array('role_id' => array('in', implode(',', $_role_department_list[$val['department_id']])),);
                        $personnel = D('Admin')->field(array('admin_id', 'role_id', 'admin_name', 'username', 'avatar'))->where($role_where)->select();
                        foreach ($personnel as $k => $v) {
                            $_ts = $v;
                            $adminRoleFind = D('AdminRole')->where(array('role_id' => $v['role_id']))->field(array('role_name'))->find();
                            $_ts['role_name'] = $adminRoleFind['role_name'];
                            $_ts['detailUrl'] = U('Platform/index/detail', array('admin_id' => $v['admin_id']));
                            if ($v['avatar']) {
                                $_ts['avatar'] = domain_name_url . '/upload/' . $v['avatar'];
                            } else {
                                $_ts['avatar'] = domain_name_url . '/public/main/images/user_default_img.jpg';
                            }
                            $personnel_list[] = $_ts;
                        }
                    }
                }
                $_t['role'] = implode(',', $_role_department_list[$val['department_id']]);
                $_t['personnel'] = $personnel_list;
                $department_list[] = $_t;
            }
            $distributorInfoFind = $this->distributorInfoService()->getServiceFind(array('distributor_id' => $adminFind['org_id']));
            $this->assign("distributorInfoFind" , $distributorInfoFind);
            //是不是后台管理员：1 是;2 店铺
            $this->assign("is_system_admin" , 2);
        }
        $this->assign('department_list', $department_list);
        $this->display();
    }
    
    protected function index2Before(){
    	$this->purviewCheck('index');
    }
    
    public function index2Action(){ /*NoPurview*/
    	$this->assign('page_title', '人员管理');
    	$this->assign('distributorListUrl', '/home/index.php/platform/index/distributorlist.html');
    	$admin_id = session('uid');
    	
    	$content = I('content');
    	$province_city_area = I('province_city_area');//
    	$distributor_id = I('distributor_id');
    	$province = I('province');
    	$city = I('city');
    	$region_code = I('region_code');
    	$this->assign("province_city_area", $province_city_area);
    	$this->assign("province", $province);
    	$this->assign("city", $city);
    	$this->assign("region_code", $region_code);
    	$this->assign("content", $content);
    	$this->assign("distributor_id", $distributor_id);
    	try {
    		$params = [
    			'admin_id'=>$admin_id,
    			'distributor_id'=>$distributor_id,
    			'content'=>$content,
    		];
    		$result = $this->oaStaffService()->salaryInfo($params);
    		$this->assign('department_list', $result['department_list']);
    		$this->assign("is_system_admin" , $result['admin_sys_id']);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->display();
    }

    private function distributorInfoService()
    {
        return D('DistributorInfo', 'Service');
    }
    
    private function oaStaffService(){
    	return D('Common/Oa/Staff', 'Service');
    }
}