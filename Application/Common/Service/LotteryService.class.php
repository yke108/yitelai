<?php
namespace Common\Service;
use Common\Basic\CsException;
use Common\Basic\Genre;
use Common\Event\PromotionCouponEvent;

class LotteryService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->lotteryInfoDao()->getRecord($id);
	}
	
	public function getInfoForUser(){
		return $this->lotteryInfoDao()->getRecordForUser();
	}
	
	public function infoCreateOrModify($params){
		if (empty($params['lottery_name'])) throw new CsException('名称不能为空', 110);
		$this->awardsCheck($params['lottery_awards']);
		$data = array(
			'lottery_name'=>trim($params['lottery_name']),
			'start_time'=>strtotime($params['start_time']),
			'end_time'=>strtotime($params['end_time']),
			'lottery_awards'=>json_encode($params['lottery_awards']),
			'is_open'=>intval($params['is_open']),
			'can_use_point'=>intval($params['can_use_point']),
			'day_times'=>intval($params['day_times']),
			'play_points'=>intval($params['play_points']),
				'lottery_brief'=>trim($params['lottery_brief']),
		);
		$lotteryInfoDao = $this->lotteryInfoDao();
		if ($params['lottery_id'] > 0){
			$data['lottery_id'] = $params['lottery_id'];
			$result = $lotteryInfoDao->saveRecord($data);
			if ($result === false){
				throw new CsException('修改失败', 111);
			}
		} else {
			$result = $lotteryInfoDao->addRecord($data);
			if ($result < 1){
				throw new CsException('添加失败', 112);
			}
		}
	}	
	
	public function infoDelete($id){
		$result = $this->lotteryInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['is_open'] && $map['is_open'] = $params['is_open'];
		if ($params['_is_validity'] == 1){
			$map['start_time'] = array('elt', NOW_TIME);
			$map['end_time'] = array('gt', NOW_TIME);
		}
		$lotteryInfoDao = $this->lotteryInfoDao();
		$count = $lotteryInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $lotteryInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function validityLotteryList(){
		$map = array(
			'is_open'=>1,
			'start_time'=>array('elt', NOW_TIME),
			'end_time'=>array('gt', NOW_TIME),
		);
		$lotteryInfoDao = $this->lotteryInfoDao();
		return $lotteryInfoDao->recordsList($map);
	}
	
	//获取机会详情
	public function getLogInfo($id){
		if(empty($id)){return '';}
		return $this->lotteryLogDao()->getRecord($id);
	} 
	
	//创建机会记录
	public function logCreate($params){
		$data = array(
			'user_id'=>$params['user_id'],
			'lottery_id'=>$params['lottery_id'],
			'chance_type'=>$params['chance_type'],
			'chance_brief'=>$params['chance_brief'],
		);
		$lotteryLogDao = $this->lotteryLogDao();
		$result = $lotteryLogDao->addRecord($data);
		if ($result === false){
			throw new CsException('操作失败', 111);
		}
	}
	
	//抽奖记录（含未抽奖的机会)
	public function logPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['lottery_id'] && $map['lottery_id'] = $params['lottery_id'];
		$params['play_time'] && $map['play_time'] = $params['play_time'];
		$params['prize_type'] && $map['prize_type'] = $params['prize_type'];
		$params['user_id'] && $map['user_id'] = $params['user_id'];
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$lotteryLogDao = $this->lotteryLogDao();
		$count = $lotteryLogDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $lotteryLogDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach ($list as $vo){
				$user_ids[$vo['user_id']] = $vo['user_id'];
				$region_codes[$vo['region_code']]=$vo['region_code'];
			}
			is_array($user_ids) && $user_list = $this->userInfoDao()->getUsers($user_ids);
			is_array($region_codes) && $regions=$this->regionDao()->getAllProvinceCity($region_codes);
			$prize_type_list = array(
				'1'=>'积分',
				'2'=>'现金',
				'3'=>'优惠券',
				'100'=>'实物',
				'101'=>'未中奖',
			);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
			'user_list'=>$user_list,
			'prize_type_list'=>$prize_type_list,
			'regions'=>$regions,
		);
	}

	public function logResult($params){
		//查找
		$lottery = $this->lotteryInfoDao()->getRecord($params['lottery_id']);
		if (empty($lottery)) throw new CsException('系统错误', 122);
		if ($lottery['start_time'] > NOW_TIME) throw new CsException('系统错误，请稍后再试', 123);
		if ($lottery['end_time'] < NOW_TIME) throw new CsException('活动已结束', 124);
		//查询机会
		$map = array(
			'user_id'=>$params['user_id'],
			'lottery_id'=>$params['lottery_id'],
		);
		$lotteryLogDao = $this->lotteryLogDao();
		$log = $lotteryLogDao->order('play_time asc')->findRecord($map);
		if ($lottery['can_use_point'] != 1){
			if (empty($log)) throw new CsException('你还没有获得抽奖的机会', 126);
			if ($log['play_time'] > 0) throw new CsException('你的抽奖机会已用完', 127);
		} elseif(empty($log) || $log['play_time'] > 0) {
			//查看是否还能使用积分抽奖
			if ($lottery['day_times'] > 0){
				$map = array(
					'user_id'=>$params['user_id'],
					'lottery_id'=>$params['lottery_id'],
					'chance_type'=>2,
					'add_time'=>array('egt', strtotime(date('Y-m-d'))),
				);
				$count = $lotteryLogDao->where($map)->count();
				if ($count >= $lottery['day_times']) throw new CsException('你的抽奖机会已用完', 127);
			}
			//生成抽奖机会
			M()->startTrans();
			$data = array(
				'user_id'=>$params['user_id'],
				'lottery_id'=>$params['lottery_id'],
				'chance_type'=>2,
				'chance_brief'=>'积分抽奖',
			);
			$log_id = $lotteryLogDao->addRecord($data);
			if ($log_id === false){
				M()->rollback();
				throw new CsException('系统错误', 111);
			}
			//扣减用户积分
			if ($lottery['play_points']){
				$p = array(
					'user_id'=>$params['user_id'],
					'points'=>$lottery['play_points'],
				);
				if ($this->pointLogic()->playLottery($p) == false){
					M()->rollback();
					throw new CsException('系统错误', 111);
				}
			}
			M()->commit();
			$log = $lotteryLogDao->getRecord($log_id);
		}
		if (empty($log)) throw new CsException('你还没有获得抽奖的机会', 126);
		if ($log['play_time'] > 0) throw new CsException('你的抽奖机会已用完', 127);
		
		//随机获得奖品
		$awards = json_decode($lottery['lottery_awards'], true);
		$r_total = $r_count = 0;
		foreach ($awards as $key => $item) {
			$r_total += intval($item['prize_ratio']);
			$awards[$key]['p_max'] = $r_total;
			$r_count++;
		}
		if ($r_total > 1){
			$r_rand = rand(1, $r_total);
			foreach ($awards as $key => $item) {
				if ($r_rand > $item['p_max']) continue;
				$prize = $item;
				$p_salt = rand(10, 90) / 100.0;
				$prize['angle'] =  -360.0 / $r_count * ($key + $p_salt) - 90;
				break;
			}
		}
		if (empty($prize)) throw new CsException('系统错误', 142);
		//保存奖品
		M()->startTrans();
		$data = array(
			'log_id'=>$log['log_id'],
			'prize_num'=>$prize['prize_num'],
			'prize_type'=>$prize['prize_type'],
			'prize_name'=>$prize['prize_name'],
			'play_time'=>NOW_TIME,
		);
		if ($this->lotteryLogDao()->saveRecord($data) < 1){
			M()->rollback();
			throw new CsException('系统错误', 127);
		}
		
		//发放奖品
		if ($prize['prize_type'] == 1){
			$p = array(
				'user_id'=>$params['user_id'],
				'points'=>$prize['prize_num'],
				'desc'=>$log['chance_brief'],
			);
			if($this->pointLogic()->winPointsOnLottery($p) === false){
				M()->rollback();
				throw new CsException('系统错误', 130);
			};
		} elseif ($prize['prize_type'] == 2){
			$p = array(
				'user_id'=>$params['user_id'],
				'money'=>$prize['prize_num'],
				'desc'=>$log['chance_brief'],
			);
			if($this->userMoneyLogic()->winMoneyOnLottery($p) === false){
				M()->rollback();
				throw new CsException('系统错误', 131);
			};
		}
		M()->commit();
		if ($prize['prize_type'] == 101){
			$prize['prize_result'] = '差一点点就中奖了';
		} else {
			$prize['prize_result'] = '恭喜您获得了'.$prize['prize_name'];
		}
		$prize['log_id']=$log['log_id'];
		return $prize;
	}
	
	//添加抽奖地址
	public function logCreateAddress($params){
		
		$log_info=$this->lotteryLogDao()->getRecord($params['log_id']);
		if(empty($log_info)){
			throw new CsException('中奖纪录不存在', 1101);
		}
		
		$address_map=array('user_id'=>$params['user_id'],'is_default'=>1);
		$address_info=$this->UserAddressDao()->findRecord($address_map);
		if(empty($address_info)){
			throw new CsException('你还未设置收货地址', 1102);
		}
		
		$data=array(
					'consignee'=>$address_info['consignee'],
					'tel'=>$address_info['mobile'],
					'region_code'=>$address_info['region_code'],
					'address'=>$address_info['address'],
					'is_select'=>1,
					'log_id'=>$params['log_id'],
				);
		$result=$this->lotteryLogDao()->saveRecord($data);
		if($result==false){
			throw new CsException('设置中奖收货地址失败', 1103);
		}
	}
	
	private function awardsCheck($awards){
		foreach ($awards as $item){
			if ($item['prize_ratio'] < 1 || $item['prize_ratio'] != intval($item['prize_ratio']))
				throw new CsException('中奖比例必须为正整数', 133);
		}
	}
	
	private function lotteryInfoDao(){
		return D('Common/Lottery/Info');
	}
	
	private function lotteryLogDao(){
		return D('Common/Lottery/Log');
	}
	
	private function pointLogic(){
		return D('Point', 'Logic');
	}
	
	private function userMoneyLogic(){
		return D('UserMoney', 'Logic');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function UserAddressDao(){
		return D('Common/User/Address');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
}