<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;
use Common\Logic\PointLogic;

class CommissionController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
	}
	
	protected function _purviewCheck(){
		$this->purviewCheck('index');
	}
	
	public function indexAction(){ /*分利明细*/
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'user_type'=>Status::UserTypeDistributorman,
		);
		//店铺
		if (session('distributor_id')) {
			$params['distributor_id'] = session('distributor_id');
		}
		if (I('keyword')) {
			$params['nick_name'] = I('keyword');
		}
		if (I('city')) {
			$params['city'] = I('city');
		}
		if (I('store_id')) {
			$params['store_id'] = I('store_id');
		}
		$result = $this->userService()->userPagerList($params);
		$this->assign('list', $result['list']);
		$this->assign('count', $result['count']);
		
		if(IS_AJAX){
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//店铺筛选
		if (session('sys_id') == Status::SysIdPlatform) {
			$map = array('status'=>Status::DistributorStatusNormal);
			$distributor_list = $this->distributorService()->getAllList($map);
			$this->assign('distributor_list', $distributor_list);
		}
		
		$this->assign('page_title', '分利明细');
		$this->display();
	}
	
	public function infoAction($user_id = 0) { /*NoPurview*/
		$info = $this->userService()->getUserInfo($user_id);
		if (empty($info)) $this->error('用户不存在');
		$this->assign('info', $info);
		
		//余额明细
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'account_type'=>Status::AccountTypeMoney,
				'change_type'=>Status::ChangeTypeCommission,
		);
		if (I('user_id')) {
			$params['user_id'] = I('user_id');
		}
		$result = $this->userAccountService()->getPagerList($params);
		foreach ($result['list'] as $k => $v) {
			$result['list'][$k]['type'] = 1;
		}
		$this->assign('account_list', $result['list']);
		
		//积分明细
		$plus_arr = array(
				PointLogic::PointTypeReg,
				PointLogic::PointTypeLogin,
				PointLogic::PointTypeIntroduce,
				PointLogic::PointTypeBirthday,
				PointLogic::PointTypeShare,
				PointLogic::PointTypeBuyGoods,
				PointLogic::PointTypeJuniorBuyGoods,
				PointLogic::PointTypePlus,
				PointLogic::PointTypeLottery,
				PointLogic::PointTypePlayLottery,
				PointLogic::PointTypeHitMouse,
				PointLogic::PointTypeComment,
				PointLogic::PointTypeCollect,
				PointLogic::PointTypeRead,
				PointLogic::PointTypePublish,
		);
		$map['change_type'] = array('in', $plus_arr);
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>$map,
		);
		if (I('user_id')) {
			$params['user_id'] = I('user_id');
		}
		$result = $this->pointService()->pointPagerList($params);
		foreach ($result['list'] as $k => $v) {
			$result['list'][$k]['type'] = 2;
		}
		$this->assign('point_list', $result['list']);
		
		$this->assign('page_title', '分利明细-详情');
		$this->display();
	}
	
	public function accountAction() { /*NoPurview*/
		$get = I('get.');
		$this->assign('get', $get);
	
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'account_type'=>Status::AccountTypeMoney,
				'change_type'=>Status::ChangeTypeCommission,
		);
		if (I('user_id')) {
			$params['user_id'] = I('user_id');
		}
		$result = $this->userAccountService()->getPagerList($params);
		$this->assign('list', $result['list']);
		$this->assign('count', $result['count']);
	
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_account');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
	
		$this->display();
	}
	
	public function pointAction() { /*NoPurview*/
		$get = I('get.');
		$this->assign('get', $get);
	
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
		);
		if (I('user_id')) {
			$params['user_id'] = I('user_id');
		}
		$result = $this->pointService()->pointPagerList($params);
		$this->assign('list', $result['list']);
		$this->assign('count', $result['count']);
	
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_point');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
	
		$this->display();
	}
	
	private function userAccountService(){
		return D('UserAccount', 'Service');
	}
	
	private function pointService(){
		return D('Point',"Service");
	}
	
	protected function distributorService() {
		return D ( 'Distributor', 'Service' );
	}
}