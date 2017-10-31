<?php
namespace Auto\Controller;
use Think\Controller;

class TeamReturnMoneyController extends Controller {
	//转化数据
	public function indexAction(){
		for ($i = 0; $i < 50; $i++){
			$result = $this->dealOne();
			if ($result === false) break;
		}
		echo 'OK';
	}
	
	//拼单失败自动退款
	public function dealOneAction(){
		header("Content-type: text/html; charset=utf-8");
		//查询拼单列表,查询出活动结束没成团的拼团
		$map=array('_string'=>"joined_num<member_num",'end_time'=>array('lt',time()),'is_return'=>0);
		$params=array('page'=>1,'pagesize'=>20,'map'=>$map);
		$result=$this->activityService()->teamPostPagerList($params);
		$post_list=$result['list'];
		if(!empty($post_list)){
			foreach($post_list as $key=>$val){
				echo '拼团id'.$val['post_id'].'<br>';
				//查询出未付款的订单，删除
				try{
					$this->orderService()->TeamReturnMoney($val['post_id']);
				}catch(\Exception $e){
					echo '拼团id'.$val['post_id'].'-'.$e->getMessage();
					die();
				}
			}
			echo "拼团退款执行成功";
		}
		
	}
	
	private function  activityService(){
		return D('Activity','Service');
	}
	private function  orderService(){
		return D('Order','Service');
	}
	
	
}