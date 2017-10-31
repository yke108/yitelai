<?php
namespace Main\Controller\Game;
use Main\Controller\MainController;

class IndexController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '首页');
    }
	
    
	
	public function indexAction(){
		
		//$user_ids=M('user_info')->order('rand()')->getField('user_id',true);
//		$user_id=$user_ids[0];
//		$number=rand(15,99);
//		header("Content-type: text/html; charset=utf-8");
//		try{
//			D('HitMouse','Service')->checkHitNumber($user_id);
//		}catch(\Exception $e){
//			echo $e->getMessage();die();
//		}
//		
//		try{
//			$params=array('user_id'=>$user_id,'number'=>$number);
//			D('HitMouse','Service')->infoCreateOrModify($params);
//		}catch(\Exception $e){
//			echo $e->getMessage();die();
//		}
//		echo '打地鼠成功';
//		die();
		
		
		$this->display();
	}
	
	public function check_hit_mouseAction(){
		$user_id=session('userid');
		
		if(empty($user_id)){
			$this->ajaxReturn(array('error'=>100,'msg'=>'请登陆'));
		}
		try{
			$this->hitMouseService()->checkHitNumber($user_id);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'验证通过'));
	}
	
	public function add_hit_logAction(){
		$post=I('post.');
		$user_id=session('userid'); 
		if(empty($user_id)){
			$this->ajaxReturn(array('error'=>100,'msg'=>'请登陆'));
		}
		
		try{
			$post['user_id']=$user_id;
			$result=$this->hitMouseService()->infoCreateOrModify($post);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>"您获得了{$result}积分"));
	}
    
    
	
	private function hitMouseService(){
		return D('HitMouse','Service');
	}
}