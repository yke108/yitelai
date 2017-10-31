<?php
namespace Common\Service;
use Common\Basic\Pager;
class ActivityService{
	
	public function getTeamFieldData($map,$field){
		return $this->teamBuyingDao()->getFieldRecord($map,$field);
	}
	
	//help_info
	public function getTeam($id,$number){
		if ($id < 1) return false;
		$info=$this->teamBuyingDao()->getRecord($id);
		$price_plan=$info['price_plan']!=''?unserialize($info['price_plan']):array();
		if(!empty($price_plan)){
			$price_plan_lang='';
			foreach($price_plan as $key2=>$val2){
				$price_plan_lang.="人数:".$val2['min_peoples'].'-'.$val2['max_peoples'].',价格:'.$val2['price']."<br>";
			}
			$info['price_plan_change']=$price_plan;
			$info['price_plan_lang']=$price_plan_lang;
		}
		if(!empty($number)){
			foreach($price_plan as $key=>$val){
				$min_peoples_arr[]=$val['min_peoples'];
				$max_peoples_arr[]=$val['max_peoples'];
				$team_price_arr[]=$val['price'];
			}
			$min_peoples=min($min_peoples_arr);
			$max_peoples=max($max_peoples_arr);
			$min_price=min($team_price_arr);
			$max_price=max($team_price_arr);
			if($number<$min_peoples){
				$number=$min_peoples;
			}
			if($number>$max_peoples){
				$number=$max_peoples;
			}
			
			foreach($price_plan as $key=>$val){
				if($val['min_peoples']<=$number &&  $number<=$val['max_peoples']){
					$price_info['price']=$val['price'];
					$price_info['min_peoples']=$val['min_peoples'];
					$price_info['max_peoples']=$val['max_peoples'];
				}
			}
			$info['price_info']=$price_info;
			$info['price_info']['number']=$number;
			$info['price_info']['min_price']=$min_price;
			$info['price_info']['max_price']=$max_price;
		}
		return $info;
	}
	
	
	public function getFind($map){
		$info=$this->teamBuyingDao()->findRecord($map);
		$info['price_plan_array']=$info['price_plan']!=''?unserialize($info['price_plan']):array();
		$price_plan=$info['price_plan_array'];
		foreach($price_plan as $key=>$val){
			$min_peoples_arr[]=$val['min_peoples'];
			$max_peoples_arr[]=$val['max_peoples'];
			$team_price_arr[]=$val['price'];
		}
		$min_peoples=min($min_peoples_arr);
		$max_peoples=max($max_peoples_arr);
		$min_price=min($team_price_arr);
		$max_price=max($team_price_arr);
		$price_info['min_price']=$min_price;
		$price_info['max_price']=$max_price;
		$price_info['min_peoples']=$min_peoples;
		$price_info['max_peoples']=$max_price;
		$info['price_info']=$price_info;
		
		$info['product']=$this->distributorGoodProductService()->getRecord($info['product_id']);
		
		return $info;
	}
	
	public function teamCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		
		//参数
		$data = array(
			'act_name'=>$params['act_name'],	
			'distributor_id'=>$params['distributor_id'],
			'goods_name'=>$params['goods_name'],	
			'goods_id'=>$params['goods_id'],	
			'product_id'=>$params['product_id'],
			'product_name'=>$params['product_name'],
			'act_img'=>$params['goods_img'],
			'start_time'=>strtotime($params['start_time']),	
			'end_time'=>strtotime($params['end_time']),	
			'limit_days'=>$params['limit_days'],
			'is_show_page'=>$params['is_show_page'],
		);
		
		$min_peoples=$params['min_peoples'];
		$max_peoples=$params['max_peoples'];
		$price=$params['price'];
		$price_plan=array();
		foreach($price as $key=>$val){
			$price_plan[$key]['price']=$val;
			$price_plan[$key]['min_peoples']=$min_peoples[$key];
			$price_plan[$key]['max_peoples']=$max_peoples[$key];
		}
		$data['price_plan']=serialize($price_plan);
		
		
		
		if(empty($params['act_id'])){
			$data['add_time']=time();
		}else{
			$data['act_id']=$params['act_id'];
		}
		
		
		$teamBuyingDao = $this->teamBuyingDao();
		if (!$teamBuyingDao->validate($rules)->create($data)){
			 throw new \Exception($teamBuyingDao->getError());
		}
		
		if ($params['act_id'] > 0){
			$result = $teamBuyingDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $teamBuyingDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function teamDelete($id){
		$result = $this->teamBuyingDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function teamPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['id'] > 0 && $map['id'] = $params['id'];
		
		if(!empty($params['status'])){
			if($params['status']==1){
				$map['start_time']=array('gt',time());
			}elseif($params['status']==2){
				$map['start_time']=array('lt',time());
				$map['end_time']=array('gt',time());
			}elseif($params['status']==3){
				$map['end_time']=array('lt',time());
			}
		}
		
		if($params['is_going']==1){
			$map['start_time']=array('lt',time());
			$map['end_time']=array('gt',time());
		}
		
		
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$teamBuyingDao = $this->teamBuyingDao();
		$count = $teamBuyingDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' act_id DESC' : $params['orderby'];
			$list = $teamBuyingDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$price_plan=$val['price_plan']!=''?unserialize($val['price_plan']):array();
				if(!empty($price_plan)){
					$price_plan_lang='';
					$min_price=0;
					$prev_price=0;
					foreach($price_plan as $key2=>$val2){
						$price_plan_lang.="人数:".$val2['min_peoples'].'-'.$val2['max_peoples'].',价格:'.$val2['price']."<br>";
						if($key2>0){
							$prev_price=$price_plan[$key2-1]['price'];
							$min_price=($val2['price']<$prev_price?$val2['price']:$prev_price);
						}else{
							$min_price=$val2['price'];
						}
					}
					
					$list[$key]['min_price']=$min_price;
					$list[$key]['price_plan_change']=$price_plan;
					$list[$key]['price_plan_lang']=$price_plan_lang;
				}
				
				//获取已成团数量
				$count_map=array('act_id'=>$val['act_id'],'_string'=>"joined_num>=member_num and joined_num<member_limit",'end_time'=>array('gt',time()));
				$list[$key]['post_count']=$this->teamBuyingPostDao()->searchRecordsCount($count_map);
				
			}
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//发起人生成活动订单
	function buildTeam($params){
		
		if(empty($params['user_id']) || empty($params['act_id'])){
			throw new \Exception('缺少参数');
		}
		
		$team_info=$this->getTeam($params['act_id']);
		if(empty($team_info)){
			throw new \Exception('团购活动不存在');
		}
		
		$user_info=$this->userInfoDao()->getRecord($params['user_id']);
		if(empty($user_info)){
			throw new \Exception('用户不存在');
		}
		
		//检查收货地址
		$address = $this->userAddressService()->findDefaultAddress($params['user_id']);
		if (empty($address)) {
			throw new \Exception('请先设置收货地址');
		}
		
		
		
		//根据提交的人数生成参数
		$member_num=$params['member_num'];
		$price_plan=unserialize($team_info['price_plan']);
		if($member_num>0){
			$max_peoples=0;
			$prev_max_peoples=0;
			foreach($price_plan as $key=>$val){
				if($val['min_peoples']<=$member_num && $member_num<=$val['max_peoples']){
					$min_member=$val['min_peoples'];
					$price=$val['price'];
					$member_limit=$val['max_peoples'];
					break;
				}
				if($key>0){
					$prev_max_peoples=$price_plan[$key-1]['max_peoples'];
				}else{
					$prev_max_peoples=$val['max_peoples'];
				}
				$max_peoples=($prev_max_peoples>$val['max_peoples'])?$prev_max_peoples:$val['max_peoples'];
			}
			if(empty($price)){
				foreach($price_plan as $key=>$val){
					if($val['min_peoples']<=$max_peoples && $max_peoples<=$val['max_peoples']){
						$min_member=$val['min_peoples'];
						$price=$val['price'];
						$member_limit=$val['max_peoples'];
						break;
					}
				}
			}
		}else{
			$price=$price_plan[0]['price'];
			$member_limit=$price_plan[0]['max_peoples'];
			$min_member=$price_plan[0]['min_peoples'];
		}
		$add_time=time();
		$end_time=$add_time+(60*60*24*$team_info['limit_days']);
		
		
		
		//参数
		$data = array(
			'act_id'=>$params['act_id'],	
			'distributor_id'=>$team_info['distributor_id'],
			'user_id'=>$params['user_id'],	
			'member_num'=>$min_member,	
			'price'=>$price,
			'add_time'=>$add_time,
			'joined_num'=>0,
			'closing_time'=>0,
			'end_time'=>$end_time,
			'member_limit'=>$member_limit,
		);
				
		
		$teamBuyingPostDao = $this->teamBuyingPostDao();
		if (!$teamBuyingPostDao->validate($rules)->create($data)){
			 throw new \Exception($teamBuyingPostDao->getError());
		}
		
		M()->startTrans();
		$result = $teamBuyingPostDao->addRecord($data);
		if ($result < 1){
			M()->rollback();
			throw new \Exception('发起团购活动失败');
		}
		
		$default_str_length=6;
		$id_leng=strlen($result);
		$id_str='';
		if($id_leng<$default_str_length){
			$id_str=sprintf("%0{$default_str_length}d", $result);
		}else{
			$id_str=$result;
		}
		
		//生成一个团号
		$post_sn=date("ymdHi").$id_str;
		$post_save_data=array('post_sn'=>$post_sn);
		$post_map=array('post_id'=>$result);
		$teamBuyingPostDao->where($post_map)->save($post_save_data);
		
		//给发起人添加一条成员记录
		$member_data=array(
			'post_id'=>$result,
			'user_id'=>$data['user_id'],
			'price'=>$data['price'],
			'is_promoter'=>1,
		);
		$log_result=$this->addMemberLog($member_data);
		
		//给发起人生成一条订单记录
		$ordr_params=array('user_id'=>$params['user_id'],'team_post_id'=>$result);
		$order_result=$this->orderService()->createTeamOrder($ordr_params);
		return $order_result;
	}
	
	public function getTeamPostField($map,$field){
		return $this->teamBuyingPostDao()->getFieldRecord($map,$field);
	}
	
	//获取发起团购的信息
	public function getTeamPost($id){
		if ($id < 1) return false;
		$info=$this->teamBuyingPostDao()->getRecord($id);
		$act_info=$this->getTeam($info['act_id']);
		unset($act_info['start_time']);
		unset($act_info['end_time']);
		unset($act_info['add_time']);
		$post_info=array_merge($info,$act_info);
		return $post_info;
	}
	
	public function getTeamPostfind($map){
		$info=$this->teamBuyingPostDao()->findRecord($map);
		$act_info=$this->getTeam($info['act_id']);
		unset($act_info['start_time']);
		unset($act_info['end_time']);
		unset($act_info['add_time']);
		$post_info=array_merge($info,$act_info);
		return $post_info;
	}
	
	
	
	//分页查询
	public function teamPostPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['post_id'] > 0 && $map['post_id'] = $params['post_id'];
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		if($params['is_going']==1){
			$map['start_time']=array('lt',time());
			$map['end_time']=array('gt',time());
		}
		
		$teamBuyingPostDao = $this->teamBuyingPostDao();
		$count = $teamBuyingPostDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'post_id DESC' : $params['orderby'];
			$list = $teamBuyingPostDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_id[]=$val['user_id'];
				$act_id[]=$val['act_id'];
			}
			$user_list=$this->userInfoDao()->getFieldRecord(array('user_id'=>array('in',$user_id)),'user_id,nick_name,user_img,headimgurl');
			$act_list=$this->getTeamFieldData(array('act_id'=>array('in',$act_id)),"act_id,goods_id,product_id,goods_name,act_img,act_name,product_name");
			foreach($list as $key=>$val){
				$list[$key]['user']=$user_list[$val['user_id']];
				$list[$key]['team']=$act_list[$val['act_id']];
			}
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//分页查询
	public function teamPostFieldPagerList($map,$field){
		return $this->teamBuyingPostDao()->getFieldRecord($map,$field);
	}
	
	//同一个商品下的团购活动列表
	public function sameGoodsTeam($record_id){
		$map=array($goods_id=>$record_id);
		$team_id=$this->teamBuyingDao()->getFieldRecord($map,'act_id',true);
		$post_map=array('act_id'=>array('in',$team_id));
		$list=$this->teamBuyingPostDao()->searchRecords($post_map,'member_limit asc',1,100);
		return $list;
	}
	
	//添加团购参与人记录
	public function addMemberLog($params){
		$post_team_info=$this->getTeamPost($params['post_id']);
		$map=array(
					'post_id'=>$params['post_id'],
					'user_id'=>$params['user_id'],
				);
		$info=$this->teamBuyingUserDao()->where($map)->find();
		if(!empty($info)){return;}
		
		$data=array(
			'post_id'=>$params['post_id'],
			'user_id'=>$params['user_id'],
			'price'=>$post_team_info['price'],
			'add_time'=>time(),
		);
		if($params['is_promoter']>0){
			$data['is_promoter']=1;
		}
		return $this->teamBuyingUserDao()->addRecord($data);
	}
	
	//修改发起团购的相关记录信息
	public function changePostTeam($params){
		$post_id=$params['post_id'];
		$user_id=$params['user_id'];
		$post_team_info=$this->getTeamPost($post_id);
		if(empty($post_team_info)){
			throw new \Exception('该拼团活动不存在');
		}
		
		$current_join_num=$post_team_info['joined_num']+1;
		
		if($current_join_num>$post_team_info['member_limit']){
			throw new \Exception('该拼团名额已满');
		}
		
		$save_data=array('joined_num'=>$current_join_num);
		
		M()->startTrans();
		
		//成团，把订单类型改为普通订单类型，之后的订单类型
		if($current_join_num==$post_team_info['member_num']){
			$save_data['closing_time']=time();
		}
		
		//成团以后就把成团之前的订单类型都设置为0
		if($current_join_num>=$post_team_info['member_num']){
			$order_type_bool=$this->orderService()->changeTeamOrderType($post_id);
			if($order_type_bool==false){
				M()->rollback();
				throw new \Exception('修改拼团状态失败');
			}
		}
		
		
		//修改参与人数
		$map=array('post_id'=>$post_id);
		$result=$this->teamBuyingPostDao()->where($map)->save($save_data);
		if($result==false){
			M()->rollback();
			throw new \Exception('修改拼团人数失败');
		}
		
		//添加一条参与人记录
		$data=array('post_id'=>$post_id,'user_id'=>$params['user_id']);
		$log_result=$this->addMemberLog($data);
		
		M()->commit();
		
		//判断成团以后把团员的那个order_type设置为0
		//$this->orderService()->changeTeamOrderType();
		
		//return true;
	}
	
	//获取拼团成员
	public function teamUserList($post_id){
		if(empty($post_id)){return ;}
		$map=array('post_id'=>$post_id);
		$size=1000;
		
		return $this->teamBuyingUserDao()->searchRecords($map,'',1,$size);
	}
	
	
	private function teamBuyingDao(){
		return D('Common/Activity/TeamBuying');
	}
	
	private function teamBuyingPostDao(){
		return D('Common/Activity/TeamBuyingPost');
	}
	
	private function teamBuyingUserDao(){
		return D('Common/Activity/TeamBuyingUser');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function orderService(){
		return D('Order','Service');
	}
	
	private function userAddressService(){
		return D('UserAddress', 'Service');
	}
	
	private function distributorGoodProductService(){
		return D('Common/Distributor/GoodsProduct');
	}
	
	
	
	
}//end HelpService!