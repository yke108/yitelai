<?php
namespace Distributor\Controller\Aftersales;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class RepairController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'aftersales',
			'ac'=>'aftersales_repair_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction() {
    	$this->listDisplay();
    }
    
    public function nocheckAction() {
    	$this->sbset('aftersales_repair_nocheck');
    	$map = array('status'=>0);
    	$this->listDisplay($map);
    }
    
    public function passAction() {
    	$this->sbset('aftersales_repair_pass');
    	$map = array('status'=>1);
    	$this->listDisplay($map);
    }
    
    public function repairingAction() {
    	$this->sbset('aftersales_repair_repairing');
    	$map = array('status'=>3);
    	$this->listDisplay($map);
    }
    
    public function backmoneyAction() {
    	$this->sbset('aftersales_repair_backmoney');
    	$map = array('status'=>5);
    	$this->listDisplay($map);
    }
	
    public function finishAction() {
    	$this->sbset('aftersales_repair_finish');
    	$map = array('status'=>6);
    	$this->listDisplay($map);
    }
    
    public function nopassAction() {
    	$this->sbset('aftersales_repair_nopass');
    	$map = array('status'=>array('in', array(2,4)));
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()) {
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    			'type'=>Status::AfterSaleRepair,
    			'map'=>$map
    	);
    	if (!empty($get['id'])) {
    		$params['id'] = $get['id'];
    	}
    	if (!empty($get['order_id'])) {
    		$params['order_id'] = $get['order_id'];
    	}
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    	
    	$datas = $this->afterSalesService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	$this->assign('total_back_money', $datas['total_back_money']);
    	
    	$this->display('index');
    }
    
    public function checkAction($id = 0) {
    	$map = array(
    			'id'=>$id,
    			'distributor_id'=>$this->org_id
    	);
    	$info = $this->afterSalesService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	if ($info['status'] != 0) {
    		$this->error('退货单已审核');
    	}
    	$this->assign('info', $info);
    	
    	//会员
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	if (empty($user)) {
    		$this->error('会员不存在');
    	}
    	$this->assign('user', $user);
    	
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['status'])) {
    			$this->error('请选择审核状态');
    		}elseif ($post['status'] == 2 && empty($post['remark'])) {
    			$this->error('审核不通过原因不能为空');
    		}
    		
    		try {
    			$post['info'] = $info;
    			$post['user'] = $user;
    			$this->afterSalesService()->backCheck($post);
    			$this->success('审核成功');
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->display();
    }
    
    public function checkViewAction($id = 0) {
    	$info = $this->afterSalesService()->getInfo($id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	//会员
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	if (empty($user)) {
    		$this->error('会员不存在');
    	}
    	$this->assign('user', $user);
    	
    	$this->display();
    }
    
    public function checkLogisticsAction($id = 0) {
    	//退货单
    	$map = array(
    			'id'=>$id,
    			'distributor_id'=>$this->org_id
    	);
    	$info = $this->afterSalesService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	if ($info['status'] != 3) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	//退货单所属会员
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	if (empty($user)) {
    		$this->error('会员不存在');
    	}
    	$this->assign('user', $user);
    	 
    	if (IS_POST) {
    		$post = I('post.');
    		if (empty($post['back_status'])) {
    			$this->error('请选择审核状态');
    		}elseif ($post['back_status'] == 4 && empty($post['instruction'])) {
    			$this->error('审核不通过原因不能为空');
    		}elseif (!in_array($post['back_status'], array(4, 5))) {
    			$this->error('审核状态不正确');
    		}
    
    		try {
    			$post['info'] = $info;
    			$post['user'] = $user;
    			$this->afterSalesService()->checkLogistics($post);
    			$this->success('审核成功');
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	 
    	$this->display();
    }
    
    public function checkBackMoneyAction($id = 0) {
    	$map = array(
    			'id'=>$id,
    			'distributor_id'=>$this->org_id
    	);
    	$info = $this->afterSalesService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	if ($info['status'] == 6) {
    		$this->error('商品已退款');
    	}
    	if ($info['status'] != 5) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	//会员
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	if (empty($user)) {
    		$this->error('会员不存在');
    	}
    	$this->assign('user', $user);
    
    	if (IS_POST) {
    		$post = I('post.');
    		$post['back_status'] = 6;
    		try {
    			$post['info'] = $info;
    			$post['user'] = $user;
    			$this->afterSalesService()->backMoney($post);
    			$this->success('审核成功');
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    
    	$this->display();
    }
}