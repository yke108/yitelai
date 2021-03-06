<?php
namespace Common\Service\Information;
class UserAccountService{
	const AccountTypeMoney = 1;
	const AccountTypePoints = 2;
	
	const ChangeTypePayOrder = 10;
	const ChangeTypeCommission = 11;
	
	public function orderAccountLog($user, $post){
		if($post['order_id'] < 0) return false;
		$data = array(
			'user_id'=>$user['user_id'],
			'amount_old'=>intval($user['user_money']),
			'order_amount'=>$post['order_amount'],
			'order_id'=>$post['order_id'],
		);
		$result = $this->userAccountDao()->payOrderByUserMoney($data);
        return $result;
	}
	
	public function commissionAccountLog($user, $post){
		if($post['order_id'] < 0) return false;
		$data = array(
				'user_id'=>$user['user_id'],
				'amount_old'=>intval($user['user_commission']),
				'order_amount'=>$post['money'],
				'order_id'=>$post['order_id'],
		);
		$result = $this->userAccountDao()->payOrderByUserMoney($data);
		return $result;
	}
	
	public function getCtypes(){
		return $this->userAccountDao()->getCtypes();
	}
	
    public function searchMoneyAccount($map, $orderBy, $start, $limit){
		$map['a.account_type'] = self::AccountTypeMoney;
        return $this->userAccountDao()->searchRecords($map, $orderBy, $start, $limit);
    }
	
    public function searchMoneyAccountCount($map){
		$map['a.account_type'] = self::AccountTypeMoney;
        return $this->userAccountDao()->searchRecordsCount($map);
    }
	
	public function getAccountRecordsLatest($user){
		$map = array(
			'user_id'=>$user['user_id'],
		);
		$postsort = 'id desc';
		return $this->userAccountDao()->where($map)->order($postsort)->limit(10)->select();
	}
	
	public  function getCommissionAmount($params){
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['user_id'])) {
			$map['a.user_id'] = $params['user_id'];
		}
		if(!empty($params['ref_user_id'])){
			$map['ref_user_id']=$params['ref_user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['a.distributor_id'] = $params['distributor_id'];
		}
		if (!empty($params['nick_name'])) {
			$map['b.nick_name'] = array('like', '%'.$params['nick_name'].'%');
		}
		if (!empty($params['change_type'])) {
			$map['change_type'] = $params['change_type'];
		}
		if (!empty($params['start_time'])) {
			$map['change_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['change_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		return $this->userAccountDao()->searchRecordsSum($map);
	}
	
	//获取分利订单总金额
	public function getOrderAmount($params){
		$map=array('change_type'=>11,'ref_id'=>array('neq',""));
		$order_ids=$this->userAccountDao()->getFieldRecord($map,"id,ref_id");
		if(!empty($order_ids)){
			$order_map=array('order_id'=>array('in',$order_ids));
			return $this->orderInfoDao()->getOrderSum($order_map);
		}else{
			return "0.00";
		}
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['user_id'])) {
			$map['a.user_id'] = $params['user_id'];
		}
		if (!empty($params['ref_user_id'])) {
			$map['a.ref_user_id'] = $params['ref_user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['a.distributor_id'] = $params['distributor_id'];
		}
		if (!empty($params['ref_id'])) {
			$map['a.ref_id'] = array('like', '%'.$params['ref_id'].'%');
		}
		if (!empty($params['nick_name'])) {
			$map['b.nick_name'] = array('like', '%'.$params['nick_name'].'%');
		}
		if (!empty($params['ref_id'])) {
			$map['ref_id'] = array('like', '%'.$params['ref_id'].'%');
		}
		if (!empty($params['mobile'])) {
			$map['b.mobile'] = array('like', '%'.$params['mobile'].'%');
		}
		if (!empty($params['change_type'])) {
			$map['change_type'] = $params['change_type'];
		}
		if (!empty($params['start_time'])) {
			$map['change_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['change_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$count = $this->userAccountDao()->searchRecordsCount($map);
		$list = array();
		if ($count) {
			$orderby = 'id desc';
			$list = $this->userAccountDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_ids[$val['ref_user_id']]=$val['ref_user_id'];
				$order_ids[$val['ref_id']]=$val['ref_id'];
			}
			$users=$this->userInfoDao()->getUsers($user_ids);
			$orders=$this->orderInfoDao()->getOrderIdsRecord($order_ids);
		}
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
				'ref_users'=>$users,
				'orders'=>$orders,
		);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	public function rewardPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['user_id'])) {
			$map['a.user_id'] = $params['user_id'];
		}
		if (!empty($params['change_type'])) {
			$map['change_type'] = $params['change_type'];
		}
		
		$count = $this->userAccountDao()->searchRecordsCount($map);
		$list = array();
		if ($count) {
			$orderby = 'id desc';
			$list = $this->userAccountDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
			$user_ids = $story_ids = array();
			foreach($list as $v){
				$user_ids[] = $v['ref_user_id'];
				$story_ids[] = $v['ref_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$storys = $this->newsInfoDao()->getFieldRecords($story_ids);
			foreach($list as $k => $v){
				$list[$k]['story_id'] = $v['ref_id'];
				$list[$k]['story_title'] = $storys[$v['ref_id']]['story_title'];
				$list[$k]['nick_name'] = $users[$v['ref_user_id']]['nick_name'];
			}
		}
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	private function userAccountDao(){
		return D('Common/Information/User/Account');
	}
	
	private function userInfoDao(){
		return D('Common/Information/User/Info');
	}
	
	private function newsInfoDao(){
		return D('Common/News/Info');
	}
}
