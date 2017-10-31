<?php
namespace Main\Controller\Index;
use Main\Controller\MainController;
use Common\Logic\PointLogic;

class CooperateController extends MainController {
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '申请合作');
		
		$params=array('page'=>1,'pagesize'=>9);
		$result=$this->brandService()->getPagerList($params);
		$this->assign('brand',$result['list']);
		
    }
	
	public function indexAction(){
		if(IS_POST){
			$post=I('post.');
			try{
				$this->cooperateService()->createOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('提交成功');
		}
		$this->display();
	}
	
	public function infoAction(){
		if(IS_POST){
			$post=I('post.');
			$post['user_id']=$this->user['user_id'];
			try{
				$this->cooperateService()->createOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('提交成功');
		}
		$this->display();
	}
	
	
	private function cooperateService(){
		return D('Cooperate', 'Service');
	}
	private function brandService(){
		return D('GoodsBrand', 'Service');
	}
	
}
