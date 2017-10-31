<?php
namespace Main\Controller\Saleman;
use Main\Controller\MainController;

class IndexController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		$this->assign('page_title', '申请分销员');
    }
	
    public function indexAction(){
		if (IS_POST) {
			$post=I('post.');
			$post['user_id']=$this->user['user_id'];
			try{
				$this->salemanService()->infoCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('申请分销员提交成功', U('user/index/index'));
		}
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display();
    }
	
	private function salemanService(){
		return D('Saleman','Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}