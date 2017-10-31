<?php
namespace Common\Service;
class HitMouseService{
	//page_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->hitMouseDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		if(empty($params['number'])){return 0;}
		if(empty($params['user_id'])){throw new \Exception('缺少参数');}
		$msg='';
		$hitMouseDao = $this->hitMouseDao();
		
		$user_info=$this->userInfoDao()->getRecord($params['user_id']);
		if(empty($user_info)){
			throw new \Exception('用户不存在');
		}
		
		//获得积分数
		$get_points=$this->givePoints($params['number']);
		
		//读取配置,获取每次最大积分
		$config_map=array('type'=>'game_hit_mouse');
		$config=$this->configDao()->findConfigs($config_map);
		$config['once_max_points']=intval($config['once_max_points'])==0?200:$config['once_max_points'];
		if($config['once_max_points']<$get_points){
			$get_points=$config['once_max_points'];
		}
		
		//每天最多获得积分数
		$day_max_points=$config['day_max_points'];
		$day_start_time=strtotime(date("Y-m-d 00:00:00"),time());
		$day_end_time=strtotime(date("Y-m-d 23:59:59"),time());
		$day_max_map=array(
						'user_id'=>$params['user_id'],
						'add_time'=>array(array('gt',$day_start_time),array('lt',$day_end_time)),
						);
		$user_day_points=$hitMouseDao->getFieldRecord($day_max_map,'sum(points)');
		if($get_points>($day_max_points-$user_day_points)){
			$get_points=$day_max_points-$user_day_points;
			$msg="每天最多获得{$day_max_points}积分,你本次游戏最多只能获得".$get_points."积分";
		}
		
		//参数
		$data = array(
			'user_id'=>trim($params['user_id']),
			'number'=>$params['number'],
			'points'=>$get_points,
			'add_time'=>time(),
		);
		
		
		
		$result = $hitMouseDao->addRecord($data);
		if ($result < 1){
			throw new \Exception('打地鼠添加积分失败');
		}
		
		//用户添加积分，加积分记录
		$point_data=array(
						'user_id'=>$data['user_id'],
						'point_old'=>$user_info['pay_points'],
						'point'=>$data['points'],
						'type'=>23,
					);
		$point_result=$this->pointLogic()->add($point_data);
		if($point_result==false){
			throw new \Exception('打地鼠添加积分失败');
		}
		
		return array('points'=>$get_points,'msg'=>$msg);
	}	
	public function infoDelete($id){
		$result = $this->hitMouseDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		if(!empty($params['keyword'])){
			$user_map=array(
							'nick_name'=>array('like',"%{$params['keyword']}%"),
							'mobile'=>array('like',"%{$params['keyword']}%"),
							'_logic'=>'or',
							);
			$user_ids=$this->userInfoDao()->getFieldRecord($user_map,'user_id',true);
			
			$map['user_id']=!empty($user_ids)?array('in',$user_ids):0;
		}
		
		$hitMouseDao = $this->hitMouseDao();
		$count = $hitMouseDao->searchRecordCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id desc' : $params['orderby'];
			$list = $hitMouseDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_ids[]=$val['user_id'];
			}
			$users=$this->userInfoDao()->getUsers($user_ids);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
			'users'=>$users,
		);
	}
	
	//打了多少个地鼠送多少分
	public function givePoints($number){
		if(intval($number)<=0){return 0;}
		$points_ratio=1;	//比例打一个送多少分，默认一分
		return $number*$points_ratio;
	}
	
	//检查用户一天内能打几次地鼠
	public function checkHitNumber($user_id){
		$today_start_time=strtotime(date("Y-m-d 00:00:00",time()));
		$today_end_time=strtotime(date("Y-m-d 23:59:59",time()));
		$hit_number=0;//打的次数
		
		//用户是否存在
		$user_info=$this->userInfoDao()->getRecord($user_id);
		if(intval($user_id)<=0 || empty($user_info)){
			throw new \Exception('用户不存在');
		}
		
		//读取配置,获取每天最大获取积分数
		$config_map=array('type'=>'game_hit_mouse');
		$config=$this->configDao()->findConfigs($config_map);
		$config['day_max_points']=intval($config['day_max_points'])==0?500:$config['day_max_points'];
		
		
		//获取用户打的次数
		$map=array(
				'user_id'=>$user_id,
				'add_time'=>array(array('gt',$today_start_time),array('lt',$today_end_time))
				);
		$user_todays_points=$this->hitMouseDao()->where($map)->getField("sum(points)");
		
		if($user_todays_points>=$config['day_max_points']){
			throw new \Exception('您今天能够获取的积分数已达到上限，请明天再来吧！');
		}
		
	}
	
	//调用model
	private function hitMouseDao(){
		return D('Common/Game/HitMouse');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function configDao(){
		return D('Common/Config');
	}
	
	private function pointLogic(){
		return D('Point','Logic');
	}
	
	
}//end HelpService!