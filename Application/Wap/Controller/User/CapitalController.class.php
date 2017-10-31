<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class CapitalController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
	
		$this->assign('page_title', '个人中心');
	}
	
	public function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$pagesize = 12;
		
		$params = array(
				'page'=>$p,
				'pagesize'=>$pagesize,
				'user_id'=>$this->user['user_id']
		);
		$datas = $this->userAccountService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		
		if(IS_AJAX){
			if(empty($datas['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			die($clist);
		}
		
		$this->display('index');
	}
	
	public function pointAction(){
		$type=I('type')?I('type'):I('get.type');
		$type=$type?$type:1;
		$this->assign('type',$type);
		$map = array(
				'a.user_id'=>$this->user['user_id']
		);
		
		if($type==2){
			$map['a.point_change']=array('gt',0);
		}elseif($type==3){
			$map['a.point_change']=array('lt',0);
		}
		$pagesize = 5;
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$pagesize,
				'map'=>$map
		);
		$datas = $this->pointService()->pointPagerList($params);
		$this->assign('list', $datas['list']);
		
		if(IS_AJAX){
			$html=$this->renderPartial('_point');
			$this->ajaxReturn(array('html'=>$html));
		}
		 
		$this->display();
	}
	
	public function commissionAction(){
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
	
	public function point_infoAction(){
		$info=$this->pageService()->getInfo(7);
		$this->assign('info',$info);
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
	
	private function newsService(){
		return D('News', 'Service');
	}
	
	private function pageService(){
		return D('Page', 'Service');
	}
}