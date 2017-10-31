<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;

class CapitalController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }

    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$pagesize = 6;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->userAccountService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function pointAction(){
    	$map = array(
    			'a.user_id'=>$this->user['user_id']
    	);
    	$pagesize = 5;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'map'=>$map
    	);
    	$datas = $this->pointService()->pointPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
		$this->display();
    }
	
	public function comissionAction(){
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'user_id'=>$this->user['user_id'],
				'change_type'=>11
		);
		$datas = $this->userAccountService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$this->display();
	}
	
	public function cashapplyAction(){
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'user_id'=>$this->user['user_id']
		);
		$datas = $this->cashApplyService()->getPagerList($params);
		
		$this->assign('list', $datas['list']);
		$this->display();
	}
	
	//提现申请
	public function cashAction(){
		$user_id=session('userid');
		$user_money=$this->userInfoService()->getFieldData(array('user_id'=>$user_id),'user_money');
		$this->assign('user_money',$user_money);
		
		$params=array('pagesize'=>100);
		$bank_result=$this->cashApplyService()->bankPagerList($params);
		if(IS_POST){
			$post=I('post.');
			$post['user_id']=$user_id;
			try{
				$this->cashApplyService()->createCashApply($post);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提现提交成功'));
		}
		$this->assign('bank_list',$bank_result['list']);
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display();
	}
	
	private function messageEvent(){
		return D('Message', 'Event');
	}
	
	private function messageService(){
		return D('Message', 'Service');
	}
	
	private function RechargeService(){
		return D('Recharge', 'Service');
	}
	
	private function userAccountService(){
		return D('UserAccount', 'Service');
	}
	
	private function cashApplyService(){
		return D('CashApply', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
	
	private function userInfoService(){
		return D('User','Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}