<?php
namespace Distributor\Controller\Personnel;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;
class AdminController extends FController {
	public function _initialize(){
		parent::_initialize();
	}


	public function distributorlistAction(){/*NoPurview*/
		$region_code = I('region_code');
		$distributor_id = I('distributor_id');
		if (empty($region_code)) $this->error('发生致命错误');
		$where = array('region_code' => $region_code);
		$data = $this->distributorInfoService()->getServiceList($where);
		$_html = "";
		$_html .= "<option value=''>请选择店铺</option>";
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

	////平台人员管理
	public function indexAction()
	{
		$this->assign('distributorListUrl', '/admin/index.php/Personnel/admin/distributorlist.html');
		$set = array(
			'in' => 'personnel',
			'ac' => 'personnel_admin_index',
		);
		$this->sbset($set);
		$admin_field = array('admin_id', 'admin_name', 'is_admin', 'role_id', 'sys_id', 'org_id');
		$department_where = array();
		$personnel_role_string = '';
		$role_id_string = '';
		$department_list = array();

		$distributor_where = array();
		$department_id = I('department_id');
		$content = I('content');
		$distributor_id = $this->org_id;
		$this->assign("department_id", $department_id);
		$this->assign("content", $content);

		//判断搜索是不是以店铺搜索
		if ($content) {
			$distributor_where['distributor_name'] = array('like', '%' . $content . '%');
		}
		$distributor_where['distributor_id'] = $distributor_id;
		$_role_department_list = array();

		$distributorInfoFind = $this->distributorInfoService()->getServiceFind($distributor_where);
		$distributorAdminFind = $this->adminService()->adminFindService(array('is_admin' => 1, 'sys_id' => 2, 'org_id' => $distributorInfoFind['distributor_id']), $admin_field);
		$admin_id = session('uid');
		$adminFind = $this->adminService()->adminFindService(array('admin_id' => $admin_id), $admin_field);

		$adminRole = D('AdminRole')->field(array('role_id', 'personnel_role'))->where(array('role_id' => $adminFind['role_id']))->find();

		$personnel = array();
		$personnel_list = array();
		$role_where = array();
		$role_where['sys_id'] = 2;
		$department_where['sys_id'] = 2;
		$department_where['org_id'] = 2;

		if ($adminFind['is_admin'] == 1) {
			$role_where['admin_id'] = array('neq', $adminFind['admin_id']);
			$adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('sys_id' => 2, 'org_id' => 2))->select();
		} else {
			$adminRoleList = D('AdminRole')->field(array('role_id', 'department_id'))->where(array('sys_id' => 2, 'org_id' => 2, 'role_id' => array('in', $adminRole['personnel_role'])))->select();
			$role_where['is_admin'] = 0;
		}

		foreach ($adminRoleList as $k2 => $v2) {
			$role_id_string .= $v2['role_id'] . ',';
			$_role_department_list[$v2['department_id']][] = $v2['role_id'];
		}

		$_other_admin_department = '';
		foreach ($_role_department_list as $k3 => $v3) {
			$_other_admin_department .= $k3 . ',';
		}
		$department_where['personnel_role'] = $_other_admin_department;
		$department = $this->adminService()->adminDepartmentService($department_where);
		$this->assign("department", $department);

		$role_where['org_id'] = $adminFind['org_id'];
		if ($department_id) {
			$role_where[] = array('role_id' => array('in', implode(',', $_role_department_list[$department_id])),);
		} else {
			$role_where[] = array('role_id' => array('in', $role_id_string),);
		}

		$personnel = D('Admin')->field(array('admin_id', 'mobile', 'role_id', 'admin_name', 'username', 'avatar'))->where($role_where)->select();
		foreach ($personnel as $k => $v) {
			$_ts = $v;
			$adminRoleFind = D('AdminRole')->where(array('role_id' => $v['role_id']))->field(array('role_name'))->find();
			$_ts['role_name'] = $adminRoleFind['role_name'];
			$_ts['detailUrl'] = U('Personnel/Admin/detail', array('admin_id' => $v['admin_id']));
			if ($v['avatar']) {
				$_ts['avatar'] = domain_name_url . '/upload/' . $v['avatar'];
			} else {
				$_ts['avatar'] = domain_name_url . '/public/main/images/user_default_img.jpg';
			}
			$personnel_list[] = $_ts;
		}
		//var_dump(M()->getLastSql());
		$this->assign("department_list" , $personnel_list);
		$this->display();
	}

	public function wagelistsAction(){
		$set = array(
			'in'=>'personnel',
			'ac'=>'personnel_admin_index',
		);
		$this->sbset($set);
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
		$wageList = $this->adminService()->pcWageService($params);
		if ($wageList['count'] > 0){
			$this->assign('list', $wageList['list']);
			$pager = new Pager($wageList['count'], $wageList['pagesize']);
			$this->assign('pager', $pager->show());
		}
		$this->assign("wageList", $wageList['list']);
		$this->assign("count", $wageList['count']);
		$this->display();
	}

	private function regionService(){
		return D('Region', 'Service');
	}

	private function distributorInfoService()
	{
		return D('DistributorInfo', 'Service');
	}

	private function oaStaffService(){
		return D('Common/Oa/Staff', 'Service');
	}
}