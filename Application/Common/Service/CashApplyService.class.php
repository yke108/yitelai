<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class CashApplyService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->cashApplyDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createCashApply($params){
		if(empty($params['user_id']) || empty($params['bank_id']) || empty($params['money'])){
			throw new \Exception('缺少参数');
		}
		
		//用户存不存在
		$user_info=$this->userInfoDao()->getRecord($params['user_id']);
		if(empty($user_info)){
			throw new \Exception('用户不存在');
		}
		
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		//提现单号
		$data['apply_id'] = date('ymdHis').rand(1000,9999);
		$data['add_time'] = NOW_TIME;
		if (!$this->cashApplyDao()->create($data)){
			 throw new \Exception($this->cashApplyDao()->getError());
		}
		
		$bank_info=$this->getBank($params['bank_id']);
		if(empty($bank_info)){
			throw new \Exception("银行不存在");
		}
		$data['bank_name']=$bank_info['bank_name'];
		
		M()->startTrans();
		
		$result = $this->cashApplyDao()->add($data);
		if (!$result){
			M()->rollback();
			throw new \Exception('提交提现申请失败');
		}
		
		//扣款
		if($this->userInfoDao()->depleteMoney($params['user_id'], $params['money']) < 1) {
			M()->rollback();
			throw new \Exception('扣款失败');
		}
		
		//冻结金额
		if($this->userInfoDao()->increaseFrozenMoney($params['user_id'], $params['money']) < 1) {
			M()->rollback();
			throw new \Exception('扣款失败');
		}
		
		//扣款日志
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		$params['user_money'] = $user['user_money'];
		if($this->userAccountDao()->cashApply($params) < 1) {
			M()->rollback();
			throw new \Exception('生成扣款日志失败');
		}
		
		M()->commit();
		return true;
	}
	
	//审核提现
	public function check($params){
		if (empty($params['status'])) throw new \Exception('请选择是否通过审核');
		if ($params['status'] == 1 && $params['remark'] == '') throw new \Exception('意见反馈不能为空');
		$limit_array = array(Status::CashNotAgree, Status::CashAgree);
		if(in_array($params['status'], $limit_array) === false) throw new \Exception('审核状态错误');
		if(empty($params['apply_id']) || empty($params['status'])) throw new \Exception('缺少参数');
		
		//提现记录
		$map = array('apply_id'=>$params['apply_id'],'status'=>Status::CashWait);
		$info = $this->cashApplyDao()->findRecord($map);
		if(empty($info)) throw new \Exception('提现记录不存在');
		
		//用户
		$user = $this->userInfoDao()->getRecord($info['user_id']);
		if(empty($user)) throw new \Exception('用户不存在');
		
		M()->startTrans();
		
		//修改审核状态
		$data=array(
				'apply_id'=>$params['apply_id'],
				'status'=>$params['status'],
				'update_time'=>NOW_TIME,
		);
		if($params['status']==Status::CashNotAgree){
			$data['remark']=$params['remark'];
		}
		$result = $this->cashApplyDao()->save($data);
		if (!$result){
			M()->rollback();
			throw new \Exception('退款审核失败');
		}
		
		if($params['status'] == 1) {
			//提现审核不通过退款
			$res = $this->userInfoDao()->increaseUserMoney($info['user_id'], $info['money']);
			if (!$res) {
				M()->rollback();
				throw new \Exception('退款失败');
			}
			
			$res = $this->userInfoDao()->depleteFrozenMoney($info['user_id'], $info['money']);
			if (!$res) {
				M()->rollback();
				throw new \Exception('退款失败');
			}
			
			//记录退款日志
			$params = array(
					'user_id'=>$user['user_id'],
					'user_money'=>$user['user_money'],
					'money'=>$info['money'],
					'apply_id'=>$info['apply_id'],
			);
			$res = $this->userAccountDao()->backCashApplyMoney($params);
			if (!$res) {
				M()->rollback();
				throw new \Exception('生成退款日志失败');
			}
		}elseif($params['status'] == 2){
			
		}else {
			M()->rollback();
			throw new \Exception('审核失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	//打款
	public function remitMoney($params){
		$limit_array=array(Status::CashRemit);
		if(empty($params['apply_id']) || empty($params['status'])){
			throw new \Exception('缺少参数');
		}
		
		$map = array('apply_id'=>$params['apply_id'],'status'=>Status::CashAgree);
		$info = $this->cashApplyDao()->findRecord($map);
		if(empty($info)) throw new \Exception('提现记录不存在');
		if(in_array($params['status'],$limit_array)==false) throw new \Exception('状态错误');
		
		M()->startTrans();
		
		$data = array(
				'apply_id'=>$params['apply_id'],
				'status'=>$params['status'],
				'certify'=>$params['certify'],
				'update_time'=>NOW_TIME,
		);
		$result = $this->cashApplyDao()->save($data);
		if ($result===false){
			M()->rollback();
			throw new \Exception('打款失败');
		}
		
		//扣除冻结金额
		$result = $this->userInfoDao()->depleteFrozenMoney($info['user_id'], $info['money']);
		if($result === false) {
			M()->rollback();
			throw new \Exception('打款失败');
		}
		
		//发送短信
		$user_info = $this->userInfoDao()->getRecord($info['user_id']);
		$data = array(
				'UserName'=>$user_info['real_name'] ? $user_info['real_name'] : $user_info['nick_name'],
				'Date'=>date('Y年m月d日 H:i'),
				'Money'=>$info['money'],
				'UserMoney'=>$user_info['user_money'],
				'UserId'=>$user_info['user_id'],
				'Title'=>'提现通知',
				'MsgType'=>Status::MsgTypeCash,
				'ApplyId'=>$info['apply_id'],
		);
		try {
			$this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnCash ,$user_info['mobile'], $data);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		M()->commit();
	}
	
	public function delete($id){
		$result = $this->cashApplyDao()->delRecord($id);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getAdminCount(){
		$map = array('a.status'=>0);
		$wait = $this->cashApplyDao()->searchRecordsCount($map);
		return array(
				'wait'=>$wait,
		);
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['user_id'])) {
			$map['a.user_id'] = $params['user_id'];
		}
		if (!empty($params['keyword'])) {
			$map['b.nick_name'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['apply_id'])) {
			$map['a.apply_id'] = array('like', '%'.$params['apply_id'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['a.add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['a.add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if(isset($params['status'])){
			$map['a.status'] = $params['status'];
		}
		//筛选分销员
		if ($params['user_type']) {
			$map['b.user_type'] = $params['user_type'];
		}
		
		$count = $this->cashApplyDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'apply_id DESC' : $params['orderby'];
			$list = $this->cashApplyDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$uids[] = $v['user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($uids);
			
			foreach ($list as $k => $v) {
				//状态
				$list[$k]['status_label'] = Status::$cashStatusList[$v['apply_status']];
				//用户
				$user = $users[$v['user_id']];
				$list[$k]['nick_name'] = $user['nick_name'];
				$list[$k]['avatar'] = $user['user_img'] ? picurl($user['user_img'], 'b90') : $user['headimgurl'];
				$list[$k]['user_name'] = $user['real_name'] ? $user['real_name'] : $user['nick_name'];
				$list[$k]['user_typename'] = Status::$userTypeList[$user['user_type']];
				//省市区
				$list[$k]['region_name'] = $this->regionDao()->getRegionName($v['region_code']);
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			//状态
			$info['status_label'] = Status::$cashStatusList[$info['status']];
		}
		return $info;
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function bonusDao(){
		return D('Bonus');
	}
	
	private function regionDao() {
		return D('Common/Region');
	}
	
	public function findFieldData($map,$field){
		return $this->storyCatDao()->findFieldRecord($map,$field);
	}
	

	public function getBank($id){
		if ($id < 1) return false;
		return $this->bankDao()->getFindRecord($id);
	}
	
	public function bankCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('bank_name', 'require', '名称是必须的'),
		);
		//参数/
		$data = array(
			'bank_name'=>trim($params['bank_name']),
			'sort_order'=>trim($params['sort_order']),
		);
		if($params['bank_id'] > 0){
			$data['bank_id'] = $params['bank_id'];
		}
		$bankDao = $this->bankDao();
		if (!$bankDao->validate($rules)->create($data)){
			 throw new \Exception($bankDao->getError());
		}
		if ($params['bank_id'] > 0){
			$result = $bankDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $bankDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function bankDelete($id){
		$info=$this->getBank($id);
		if(empty($info)){throw new \Exception('记录不存在');}
		$result = $this->bankDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function bankPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		//搜索//
		!empty($params['cat_name']) && $map['cat_name'] = array('like','%'.$params['cat_name'].'%');
		$bankDao = $this->bankDao();
		$count = $bankDao->searchRecordCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $bankDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function cashApplyDao(){
		return D('Common/CashApply');
	}
	
	public function bankDao(){
		return D('Common/Bank');
	}
	
	private function smsLogic(){
		return D('Sms', 'Logic');
	}
}