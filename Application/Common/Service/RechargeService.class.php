<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class RechargeService{
	const RechargeStatusNone = 0;
	const RechargeStatusDone = 1;
	
	public function getInfo($id){
		
		return $this->rechargeDao()->getFindRecord($id);
	}
	
	public function getRecharge($id){
		$info = $this->rechargeDao()->rechargeInfo($id);
		return $this->dealOutput($info);
	}
	
	public function getInfoFind($map){
		$info=$this->rechargeDao()->findRecord($map);
		return $info;
	}
    
    public function getPagerList($params){
    	if ($params['page'] < 1 || $params['pagesize'] < 1) throw new \Exception('分页参数不正确');
    	$map = array(
    		'pay_status'=>self::RechargeStatusDone,
    	);
    	$params['type'] > 0 && $map['type'] = $params['type'];
    	$params['user_id'] > 0 && $map['user_id'] = $params['user_id'];
    	$params['start_time'] && $map['pay_time'] = array('egt', strtotime($params['start_time']));
		$params['end_time'] && $map['pay_time'] = array('lt', strtotime($params['end_time']) + 86400);
		$orderBy = 'recharge_id desc';
		
		$count = $this->rechargeDao()->where($map)->count();
		$list = array();
		if ($count > 0){
			$t = $this->rechargeDao()->where($map)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
	        foreach ($t as $vo){
	        	$list[] = $this->dealOutput($vo);
	        }
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
    }
    
    public function paySuccess($params){
    	$order_sn = $params['order_sn']; //店铺订单号
		$oid_paybill = $params['transaction_id']; //交易单号
		$order_amount = $params['order_amount']; //支付金额
		$pre_code = $order_sn[0];
		if ($pre_code != 'B') throw new \Exception('未知订单号'); 
		$map=array('recharge_sn'=>$order_sn);
		$info=$this->findInfo($map);
		if (empty($info)) throw new \Exception('订单不存在'); 
		if ($info['pay_status'] == 1) throw new \Exception('订单已处理');
		
		M()->startTrans();
		
		//更改订单状态
		$data = array(
			'pay_status'=>1,
			'pay_time'=>NOW_TIME,
			'pay_id'=>$params['pay_id'],
		);
		try {
			$this->editRecharge($info['recharge_id'], $data);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//添加充值记录
		$user_money=$this->userInfoDao()->getFieldRecord(array('user_id'=>$info['user_id']),'user_money');
		$log_params=array('user_id'=>$info['user_id'],'user_money'=>$user_money,'money'=>$info['recharge_amount']);
		$log_result=$this->userAccountDao()->RechargeLog($log_params);
		if($log_result === false){
			M()->rollback();
			throw new \Exception('生成充值记录失败');
		}
		
		//增加用户金额
		if($info['activity_id']==0){
			$result = $this->userInfoDao()->increaseUserMoney($info['user_id'], $info['recharge_amount']);
		}else{
			$result = $this->userInfoDao()->increaseFrozenRechargeMoney($info['user_id'], $info['recharge_amount']);
		}
		if ($result < 1){
			M()->rollback();
			throw new \Exception('金额修改失败');
		}
		
		//发送短信
		$user_info = $this->userInfoDao()->getRecord($info['user_id']);
		$data = array(
				'UserName'=>$user_info['real_name'] ? $user_info['real_name'] : $user_info['nick_name'],
				'Date'=>date('Y年m月d日 H:i'),
				'Money'=>$info['recharge_amount'],
				'UserMoney'=>$user_info['user_money'],
				'UserId'=>$user_info['user_id'],
				'Title'=>'充值通知',
				'MsgType'=>Status::MsgTypeRecharge,
				'RechargeId'=>$info['recharge_id'],
		);
		try {
			$this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnRecharge ,$user_info['mobile'], $data);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		M()->commit();
		
		return true;
    }
	
	public function payStart($params){
		$user_id = intval($params['user_id']);
		if($user_id < 1) throw new \Exception('未知错误');
		$t_number = intval($params['number']);
		$config = $this->configService()->findCoinRechargeConfigs();
		if ($t_number < $config['min_limit']) throw new \Exception('购买数量最少为'.$config['min_limit']);
		$pay_id = $params['pay_id'];
		if (!in_array($pay_id, array('3'))) throw new \Exception('未知的支付方式');
		
		$money = $t_number * $config['price'] / 100;
		$data = array(
			'recharge_id'=>date('ymdHis').rand(1000, 9999),
			'user_id'=>$params['user_id'],
			'add_time'=>NOW_TIME,
			'recharge_amount'=>$money,
			'recharge_number'=>$t_number,
			'pay_id'=>$pay_id,
		);
		$result = $this->rechargeDao()->add($data);
		if ($result < 1) {
			throw new \Exception('操作失败');
		}
		return $result;
	}
	
	public function findInfo($map){
		
		return $this->rechargeDao()->findRecord($map);
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		
		$rechargeDao = $this->rechargeDao();
		$count = $rechargeDao->searchRecordCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time DESC' : $params['orderby'];
			$list = $rechargeDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//生成充值订单
	public function addRecharge($params){
		if(!empty($params['user_id']) && ($params['recharge_amount']=='' || $params['activity_id']=='')){throw new \Exception('缺少参数');}
		$order_sn ='B'.time().mt_rand(100,999);
		$data=array(
			'recharge_sn'=>$order_sn,
			'user_id'=>$params['user_id'],
			'recharge_amount'=>$params['recharge_amount'],
			'type'=>0,
			'pay_status'=>0,
			'add_time'=>time(),
		);
		
		//是否选择了充值活动
		if($params['activity_id']>0){
			$activity_info=$this->getActivity($params['activity_id']);
			if(empty($activity_info)){
				throw new \Exception('充值活动不存在');
			}
			$data['recharge_amount']=$activity_info['limit_money'];
			$data['activity_id']=$params['activity_id'];
			$data['revalue_days']=$activity_info['days'];
			$data['days_money']=$activity_info['money'];
			$data['already_revalue_days']=0;
			$data['revalue_is_end']=0;
		}
		
		$recharge_id=$this->rechargeDao()->addRecord($data);
		if($recharge_id==false){throw new \Exception('充值失败');}
		$recharge_sn=$this->rechargeDao()->getFieldRecord(array('recharge_id'=>$recharge_id),'recharge_sn');
		return $recharge_sn;
		
	}
	
	//增值操作程序
	public function revalueMoney(){
		//$map=array(''=>array('gt'=>0),'revalue_is_end'=>0);
		$current_start_time=strtotime(date("Y-m-d 00:00:00",time()));
		$current_end_time=strtotime(date("Y-m-d 23:59:59",time()));
		$revalue_map['_string']="rr.recharge_id=a.recharge_id";
		$revalue_map['add_time'][]=array('gt',$current_start_time);
		$revalue_map['add_time'][]=array('lt',$current_end_time);
		$revalue_log_sql=M('recharge_revalue')->alias('rr')->field('count(rr.revalue_id)')->where($revalue_map)->buildSql();
		$map="a.activity_id>0 and revalue_is_end=0";
		$limit=20;
		
		
		do{
			M()->startTrans();
			
			$list=M('recharge')
			->alias('a')
			->field("a.*,$revalue_log_sql today_revalue")
			->where($map)
			->having("today_revalue=0 or today_revalue is null")
			->page(1,$limit)
			->select();
			
			if(!empty($list)){
				foreach($list as $key=>$val){
					$current_day=$val['already_revalue_days']+1;
					$grand_total_money=$current_day*$val['days_money'];
					$recharge_amount=$val['recharge_amount'];
					
					if($current_day>$val['revalue_days']){
						throw new \Exception($val['recharge_sn'].'当前增值天数不能大于总增值天数');
					}
					
					//修改充值订单增值相关字段
					$recharge_data=array(
						'already_revalue_days'=>$current_day,
						'grand_total_money'=>$grand_total_money,
						'amount_money'=>$recharge_amount+$grand_total_money,
						'recharge_id'=>$val['recharge_id'],
					);
					
					//如果当前更新天数等于增值总天数就把增值结束状态改为1
					if($current_day==$val['revalue_days']){
						$recharge_data['revalue_is_end']=1;
					}
					
					//添加用户充值冻结金额
					$user_result = $this->userInfoDao()->increaseFrozenRechargeMoney($val['user_id'], $grand_total_money);
					if($user_result==false){
						M()->rollback();
						throw new \Exception($val['recharge_sn'].'用户充值冻结金额增加失败');
						break;
					}
					
					$recharge_result=$this->rechargeDao()->updateRecord($recharge_data);
					if($recharge_result==false){
						M()->rollback();
						throw new \Exception($val['recharge_sn'].'更新订单增值字段失败');
						break;
					}
					
					//添加增值记录
					$revalue_data=array(
									'recharge_id'=>$val['recharge_id'],
									'day'=>$current_day,
									'user_id'=>$val['user_id'],
									'money'=>$val['days_money'],
									'add_time'=>time(),
								);
					$revalue_result=$this->rechargeRevalueDao()->addRecord($revalue_data);
					if($revalue_result==false){
						M()->rollback();
						throw new \Exception($val['recharge_sn'].'更新增值记录失败');
						break;
					}
				}	
			}
			
			M()->commit();
		}while(!empty($list));
		
		
		
		return '更新增值记录成功';
		
	}
	
	private function editRecharge($id, $data){
    	$map = array(
    		'recharge_id'=>$id,
    	);
    	$result = $this->rechargeDao()->where($map)->save($data);
    	if ($result < 1) {
			throw new \Exception('操作失败');
		}
		return $result;
	}
	
	private function rechargeDao(){
		return D('Common/Recharge/Recharge');
	}
	
	private function rechargeRevalueDao(){
		return D('Common/Recharge/RechargeRevalue');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	
    protected function configService(){
    	return D('Config', 'Service');
    }
	
	
	public function getActivity($id){
		if ($id < 1) return false;
		$info=$this->activityDao()->getFindRecord($id);
		return $info;
	}
	
	public function activityCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'title'=>$params['title'],
			'limit_money'=>$params['limit_money'],	
			'money'=>$params['money'],	
			'days'=>$params['days'],	
			'description'=>$params['description'],	
			'start_time'=>strtotime($params['start_time']),	
			'end_time'=>strtotime($params['end_time']),	
			'sort_order'=>$params['sort_order'],	
			'status'=>$params['status'],
		);
		
		
		if($params['activity_id'] > 0){
			$data['activity_id']=$params['activity_id'];
		}else{
			$data['add_time'] = time();
		}
		
		$activityDao = $this->activityDao();
		if (!$activityDao->validate($rules)->create($data)){
			 throw new \Exception($activityDao->getError());
		}
		if ($params['activity_id'] > 0){
			$result = $activityDao->updateRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $activityDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	
	public function activityDelete($map){
		$result = $this->activityDao()->deleteRecord($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function activityPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['activity_id'] > 0 && $map['activity_id'] = $params['activity_id'];
		
		isset($params['status']) && $map['status']=$params['status'];
		if($params['is_going']==1){
			$map['status']=1;
			$map['start_time'][]=array('lt',time());
			$map['end_time'][]=array('gt',time());
		}
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$activityDao = $this->activityDao();
		$count = $activityDao->searchRecordCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, activity_id DESC' : $params['orderby'];
			$list = $activityDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//修改活动状态
	public function changeStatus($params){
		$data=array('activity_id'=>$params['activity_id'],'status'=>$params['status']);
		$result=$this->activityDao()->updateRecord($data);
		if($result==false){
			throw new \Exception('修改状态失败');
		}
	}
	
	private function activityDao(){
		return D('Common/Recharge/RechargeActivity');
	}
	
	
	//充值记录
	public function getLog($id){
		if ($id < 1) return false;
		$info=$this->rechargeLogDao()->getFindRecord($id);
		return $info;
	}
	
	public function logCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'recharge_id'=>$params['title'],
			'user_id'=>$params['limit_money'],	
			'type'=>$params['type']?$params['type']:0,	
			'days'=>$params['days'],	
			'amount'=>$params['amount'],	
			'pay_id'=>$params['pay_id'],	
			'add_time'=>time(),	
		);
		
		
		$rechargeLogDao = $this->rechargeLogDao();
		if (!$activityDao->validate($rules)->create($data)){
			 throw new \Exception($activityDao->getError());
		}
		
		$result = $rechargeLogDao->addRecord($data);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
		
	}	
	
	public function logDelete($map){
		$result = $this->rechargeLogDao()->deleteRecord($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function logPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['activity_id'] > 0 && $map['activity_id'] = $params['activity_id'];
		
		isset($params['status']) && $map['status']=$params['status'];
		if($params['is_going']==1){
			$map['status']=1;
			$map['start_time'][]=array('lt',time());
			$map['end_time'][]=array('gt',time());
		}
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$rechargeLogDao = $this->rechargeLogDao();
		$count = $rechargeLogDao->searchRecordCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, activity_id DESC' : $params['orderby'];
			$list = $rechargeLogDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	
	private function rechargeLogDao(){
		return D('Common/Recharge/RechargeLog');
	}
	
	private function smsLogic(){
		return D('Sms', 'Logic');
	}
}