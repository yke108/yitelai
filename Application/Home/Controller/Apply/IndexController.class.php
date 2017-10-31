<?php
namespace Home\Controller\Apply;
use Home\Controller\BaseController;
use Common\Basic\CsException;
use Common\Basic\ApplyConst;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		$verify_status_list = [
			ApplyConst::VerifyStatusStart => ['待审批', 'warning'],
			ApplyConst::VerifyStatusWaitSignAgain => ['待复审', 'warning'],
			ApplyConst::VerifyStatusTransfer => ['转出', 'success'],
			ApplyConst::VerifyStatusOk => ['已批准', 'success'],
			ApplyConst::VerifyStatusCancel => ['已取消', 'warning'],
			ApplyConst::VerifyStatusFail => ['已驳回', 'danger'],
		];
		$this->assign('verify_status_list', $verify_status_list);
    }
    
    protected function indexBefore(){
    	$this->purviewCheck(false);
    }
    
	// 首页
    public function indexAction() /*NoPurview*/{
		// 审批数量
		if (haspv('check')){
			$params = [
				'admin_id'=>session('uid'),
				'verify_status'=>ApplyConst::VerifyStatusStart,
			];
			$wait_number = $this->applyService()->verifyWaitNumber($params);
			$this->assign('wait_number', $wait_number);
			$this->assign('hascheck', 1);
		}
		
		// 最近3次申请记录
		$params = [
			'admin_id'=>session('uid'),
			'pagesize'=>3,
		];
		$three = $this->applyService()->gethistory($params);
		$this->assign('three',$three);
		
		$this->assign('page_title','申请管理');
		$this->display();
    }
    
    protected function addBefore(){
    	$this->purviewCheck(false);
    }
	
	// 添加申请
	public function addAction() /*NoPurview*/{ 
		if(IS_POST){ 
			$post = I('post.');
			// 3种申请1店铺内部2店铺对平台3代理商对平台
			$post['type'] = 1;
			$post['price'] = $post['price'] ? $post['price'] : 0;
			$post['admin_id'] = session('uid');
			$post['distributor_id'] = session('distributor_id');
			$result = $this->applyService()->add($post);
			$this->success('提交成功', U('apply/index/detail', ['id'=>$result]));
		}
		// 申请类型
		$apply_type = $this->applyService()->gettype();
		$type_list = [];
		foreach ($apply_type as $vo){
			if (!haspv($vo['apply_purview'])) continue;
			unset($vo['apply_level'], $vo['apply_purview'], $vo['verify_purview']);
			$type_list[] = $vo;
		}
		$this->assign('apply_type',$type_list);
		$this->assign('page_title','申请');
		$this->display();
	}
	
	protected function adminsBefore(){
		$this->purviewCheck(false);
	}
	
	//审批人
	public function adminsAction(){ /*NoPurview*/
		$post = I('post.');
		if ($post['apply_type'] < 1) $this->ajaxReturn(['status'=>0]);
		$type = $this->applyService()->getTypeById($post['apply_type']);
		if (empty($type)) $this->ajaxReturn(['status'=>0]);
		$admin = $this->adminService()->getAdmin(session('uid'));
		//默认平台审批
		$sys_id = $org_id = 1;
		//为1时内部审批
		if ($type['apply_level'] == 1){
			$sys_id = $admin['sys_id'];
			$org_id = $admin['org_id'];
		}
		$purview = $type['verify_purview'];
		//不能是本人、是本系统还是平台、有没有相应的权限
		$map = [
			'admin_id'=>['neq', session('uid')],
			'sys_id'=>$sys_id,
			'org_id'=>$org_id,
		];
		if ($purview) $map['_string'] = 'oa_action_list="all" or LOCATE(",'.$purview.',", oa_action_list)';
		$ll = $this->adminService()->adminList($map);
		$list = [];
		foreach ($ll as $vo){
			$item = [
				'admin_id'=>$vo['admin_id'],
				'admin_name'=>$vo['admin_name'],
				'label'=>'',
			];
			if ($vo['admin_id'] == $admin['parent_id']){
				$item['label'] = '上司';
				array_unshift($list, $item);
			} else {
				$list[] = $item;
			}
		}
		//申请特殊字段
		$this->assign('type', $type);
		$data = [
			'list'=>$list,
			'status'=>1,
			'htm_ext'=>$this->renderPartial('_add'),
		];
		$this->ajaxReturn($data);
	}
	
	protected function historyBefore(){
		$this->purviewCheck(false);
	}
	
	// 申请记录
	public function historyAction() /*NoPurview*/ {
		$params = [
			'admin_id'=>session('uid'),
		];
		$list = $this->applyService()->gethistory($params);
		$this->assign('list',$list);
		$this->assign('page_title','申请记录');
		$this->display();
	}
	
	protected function detailBefore(){
		$this->purviewCheck(false);
	}
	
	// 申请详情
	public function detailAction() /*NoPurview*/ {
		$get = I('get.');
		$info = $this->applyService()->getdetail($get['id']);
		$info['status_label'] = ApplyConst::$verify_status_list[$info['verify_status']];
		$info['apply_name'] = ApplyConst::$appy_type_list[$info['apply_type']];
		$info['pics'] = picurls($info['pics']);
		$info['attachments'] = picurls($info['attachments']);
		$this->assign('info',$info);
		$this->assign('page_title','申请详情');
		$this->display();
	}
	
	protected function cancelBefore(){
		$this->purviewCheck(false);
	}
	
	public function cancelAction(){ /*NoPurview*/
		$get = I('get.');
		try {
			$params = [
				'apply_id'=>$get['id'],
				'operator_id'=>session('uid'),
			];
			$this->applyService()->cancelApply($params);
			$this->success('操作成功');
		} catch (\Exception $e){
			$this->error($e->getMessage());
		}
	}
	
	protected function retryBefore(){
		$this->purviewCheck(false);
	}
	
	public function retryAction(){ /*NoPurview*/
		$get = I('get.');
		try {
			$params = [
				'apply_id'=>$get['id'],
				'operator_id'=>session('uid'),
			];
			$this->applyService()->retryApply($params);
			$this->success('操作成功');
		} catch (\Exception $e){
			$this->error($e->getMessage());
		}
	}
	
	// 审批列表
	public function checkAction() /*审批列表*/ {
		$get = I('get.');
		$params = [
			'operator_id'=>session('uid'),
			'pagesize'=>20,
		];
		$admins = [];
		//全部
		$result = $this->applyService()->verifyPagerList($params);
		$this->assign('list',$result['list']);
		foreach ($result['admins'] as $ko => $vo){
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$admins[$ko] = $vo;
		}
		//待审批
		$params['verify_status'] = ['in', [ApplyConst::VerifyStatusStart, ApplyConst::VerifyStatusWaitSignAgain]];
		$result = $this->applyService()->verifyPagerList($params);
		$this->assign('list2',$result['list']);
		foreach ($result['admins'] as $ko => $vo){
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$admins[$ko] = $vo;
		}
		//审批通过
		$params['verify_status'] = ApplyConst::VerifyStatusOk;
		$result = $this->applyService()->verifyPagerList($params);
		$this->assign('list3',$result['list']);
		foreach ($result['admins'] as $ko => $vo){
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$admins[$ko] = $vo;
		}
		//审批不通过
		$params['verify_status'] = ApplyConst::VerifyStatusFail;
		$result = $this->applyService()->verifyPagerList($params);
		$this->assign('list4',$result['list']);
		foreach ($result['admins'] as $ko => $vo){
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$admins[$ko] = $vo;
		}
		$this->assign('page_title','审批列表');
		$this->assign('admins', $admins);
		$this->assign('get', $get);
		$this->display();
	}
	
	protected function checkajaxBefore(){
		$this->purviewCheck('check');
	}
	
	public function checkajaxAction() /*NoPurview*/ {
		if (!IS_AJAX) exit('');
		$get = I('get.');
		$params = [
			'operator_id'=>session('uid'),
			'page'=>$get['p'],
			'pagesize'=>20,
		];
		if ($get['type'] == 2){
			$params['verify_status'] = ['in', [ApplyConst::VerifyStatusStart, ApplyConst::VerifyStatusWaitSignAgain]];
		} elseif ($get['type'] == 3){
			$params['verify_status'] = ApplyConst::VerifyStatusOk;
		} elseif ($get['type'] == 4){
			$params['verify_status'] = ApplyConst::VerifyStatusFail;
		}
		$result = $this->applyService()->verifyPagerList($params);
		$this->assign('list',$result['list']);
		$admins = [];
		foreach ($result['admins'] as $ko => $vo){
			$vo['avatar'] = admin_avatar($vo['avatar']);
			$admins[$ko] = $vo;
		}
		$this->assign('admins', $admins);
		$this->assign('get', $get);
		$clist = empty($result['list']) ? '' : $this->renderPartial('_check');
		$ret = [
			'html'=>$clist,
			'p'=>$result['page'] + 1,
		];
		$this->ajaxReturn($ret);
	}

	// 审批详情
	protected function checkdetailBefore(){
		$this->purviewCheck('check');
	}
	
	public function checkdetailAction() /*NoPurview*/ { 
		$get = I('get.');
		$params = [
			'verify_id'=>$get['id'],
			'operator_id'=>session('uid'),
		];
		$info = $this->applyService()->getVerifyDetail($params);
		$this->assign('info',$info);
		$this->assign('page_title','审批详情');
		
		$type_id = $info['apply_info']['apply_type'];
		$type = $this->applyService()->getTypeById($type_id);
		$admin = $this->adminService()->getAdmin($info['apply_info']['admin_id']);
		if ($type && $admin){
			//默认平台审批
			$sys_id = $org_id = 1;
			//为1时内部审批
			if ($type['apply_level'] == 1){
				$sys_id = $admin['sys_id'];
				$org_id = $admin['org_id'];
			}
			$purview = $type['verify_purview'];
			//不能是本人、是本系统还是平台、有没有相应的权限
			$map = [
				'admin_id'=>['not in', [$params['operator_id'], $admin['admin_id']]],
				'sys_id'=>$sys_id,
				'org_id'=>$org_id,
			];
			if ($purview) $map['_string'] = 'oa_action_list="all" or LOCATE(",'.$purview.',", oa_action_list)';
			$ll = $this->adminService()->adminList($map);
			$list = [];
			foreach ($ll as $vo){
				$item = [
					'admin_id'=>$vo['admin_id'],
					'admin_name'=>$vo['admin_name'],
					'avatar'=>admin_avatar($vo['avatar']),
					'label'=>'',
				];
				$list[] = $item;
			}
			$this->assign('verify_admin_list', $list);
		}
		$this->display();
	}
	
	protected function transferBefore(){
		$this->purviewCheck('check');
	}
	
	public function transferAction() /*NoPurview*/ {
		$data = I('post.');
		$data['operator_id'] = session('uid');
		try {
			$this->applyService()->transfer($data);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功');
	}
	
	// 审批留言
	public function checknoteAction() /*审批留言*/ {
		if(IS_POST){ 
			$data = I('post.');
			$data['operator_id'] = session('uid');
			try {
				$this->applyService()->verify($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功');
		}
		$this->assign('get',I('get.'));
		$this->assign('page_title','审批留言');
		$this->display();
	}
	
	public function inAction() /*内部申请*/ { /*仅作为权限管理,请假、出差、报销, 提交时需指定审批人*/}
	public function prAction() /*价格特批申请*/ { /*仅作为权限管理, */}
	public function wjAction() /*申请事件文件*/ { /*仅作为权限管理, */}
	public function ybAction() /*申请样板*/ { /*仅作为权限管理, */}
	public function zcAction() /*申请支持*/ { /*仅作为权限管理, */}
	
	public function inxAction() /*内部申请审批*/ { /*仅作为权限管理,请假、出差、报销, 提交时需指定审批人*/}
	public function prxAction() /*价格特批申请审批*/ { /*仅作为权限管理, */}
	public function wjxAction() /*事件文件申请审批*/ { /*仅作为权限管理, */}
	public function ybxAction() /*样板申请审批*/ { /*仅作为权限管理, */}
	public function zcxAction() /*申请支持审批*/ { /*仅作为权限管理, */}
	
	protected function applyService(){
		return D('Apply', 'Service');
	}
}