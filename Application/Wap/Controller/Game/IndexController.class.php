<?php
namespace Wap\Controller\Game;
use Wap\Controller\WapController;

class IndexController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '首页');
    }
	
    
	
	public function indexAction(){
		
		if(session('userid')==''){
			$this->redirect('index/site/login');
		}
		
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
		
		$msg=empty($result['msg'])?"您获得了{$result[points]}积分":$result['msg'];
		$this->ajaxReturn(array('error'=>0,'msg'=>$msg));
	}
    
	public function turn_tableAction(){
		//抽奖
		$lottery = $this->lotteryService()->getInfoForUser();
		$awards = array();
		$lottery['lottery_awards'] = json_decode($lottery['lottery_awards'], true);
		foreach ($lottery['lottery_awards'] as $vo){
			$awards[] = $vo['prize_name'];
		}
		$lottery['lottery_awards'] = $awards;
		$this->assign('lottery', $lottery);
		//var_dump($lottery);die();
		//抽奖记录
		$params = array(
			'lottery_id'=>$lottery['lottery_id'],
			'orderby'=>'play_time desc',
			'play_time'=>array('gt', 0),
			'prize_type'=>array('neq', 101),
			'pagesize'=>10,
		);
		$result = $this->lotteryService()->logPagerList($params);
		$this->assign('log_list', $result['list']);
		//var_dump($result);die();
		$this->assign('user_list', $result['user_list']);
		
		//个人抽奖记录总数
		$map=array('prize_type'=>array(array('gt',0),array('lt',101)));
		$params=array('user_id'=>$this->user['user_id'],'map'=>$map);
		$log_result=$this->lotteryService()->logPagerList($params);
		$this->assign('prize_count',$log_result['count']);
		
		$this->display();
	}
    
	public function exchangeAction(){
		$log_id=I('log_id')?I('log_id'):I('get.log_id');
		$info=$this->lotteryService()->getLogInfo($log_id);
		$this->assign('info',$info);
		if(empty($info) || $info['is_select']==1){
			$this->redirect('user/account/prize');
		}
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		$this->display();
	}
	
	public function log_set_addressAction(){
		$log_id=I('log_id')?I('log_id'):I('get.log_id');
		$post=array('user_id'=>$this->user['user_id'],'log_id'=>$log_id);
		try{
			$this->lotteryService()->logCreateAddress($post);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('奖品地址设置成功');
	}
	
	private function hitMouseService(){
		return D('HitMouse','Service');
	}
	
	private function lotteryService(){
    	return D('Lottery', 'Service');
    }
	
	private function userAddressService(){
    	return D('UserAddress', 'Service');
    }
}