<?php
namespace Common\Service\Distributor;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class CashApplyService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->distributorCashApplyDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createCashApply($params){
		if(empty($params['distributor_id'])){
			throw new \Exception('缺少参数');
		}
		
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//店铺不存在
		$distributor_info=$this->distributorInfoDao()->getRecord($params['distributor_id']);
		if(empty($distributor_info)){
			throw new \Exception('店铺不存在');
		}
		if($distributor_info['money'] < $params['money']){
			throw new \Exception('余额不足');
		}
		
		$bank_info=$this->getBank($params['bank_id']);
		if(empty($bank_info)){
			throw new \Exception("请选择银行");
		}
		$data['bank_name']=$bank_info['bank_name'];
		
		//提现单号
		$data['apply_id'] = date('ymdHis').rand(1000,9999);
		$data['add_time'] = NOW_TIME;
		if (!$this->distributorCashApplyDao()->create($data)){
			 throw new \Exception($this->distributorCashApplyDao()->getError());
		}
		
		M()->startTrans();
		
		$result = $this->distributorCashApplyDao()->add();
		if (!$result){
			M()->rollback();
			throw new \Exception('提交提现申请失败');
		}
		
		//扣款
		if($this->distributorInfoDao()->depleteMoney($params['distributor_id'], $params['money']) < 1) {
			M()->rollback();
			throw new \Exception('扣款失败');
		}
		
		//冻结金额
		if($this->distributorInfoDao()->increaseFrozenMoney($params['distributor_id'], $params['money']) < 1) {
			M()->rollback();
			throw new \Exception('扣款失败');
		}
		
		//扣款日志
		$user = $this->distributorInfoDao()->getRecord($params['distributor_id']);
		$params['money'] = $user['money'];
		$params['distributor_name'] = $distributor_info['distributor_name'];
		if($this->distributorAccountDao()->cashApply($params) < 1) {
			M()->rollback();
			throw new \Exception('生成扣款日志失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	//审核提现
	public function check($params){
		$limit_array=array(Status::CashNotAgree,Status::CashAgree);
		if(in_array($params['status'],$limit_array)==false){
			throw new \Exception('审核状态错误');
		}
		if(empty($params['apply_id']) || empty($params['status'])){
			throw new \Exception('缺少参数');
		}
		
		//提现记录
		$map=array('apply_id'=>$params['apply_id'],'status'=>Status::CashWait);
		$info=$this->distributorCashApplyDao()->findRecord($map);
		if(empty($info)){
			throw new \Exception('提现记录不存在');
		}
		
		//店铺
		$distributor = $this->distributorInfoDao()->getRecord($info['distributor_id']);
		if(empty($distributor)){
			throw new \Exception('店铺不存在');
		}
		
		M()->startTrans();
		
		//修改审核状态
		$data=array('apply_id'=>$params['apply_id'],'status'=>$params['status']);
		if($params['status']==Status::CashNotAgree){
			$data['remark']=$params['remark'];
		}
		$result = $this->distributorCashApplyDao()->save($data);
		if (!$result){
			M()->rollback();
			throw new \Exception('退款审核失败');
		}
		
		if($params['status'] == 1) {
			//提现审核不通过退款
			$res = $this->distributorInfoDao()->increaseMoney($info['distributor_id'], $info['money']);
			if (!$res) {
				M()->rollback();
				throw new \Exception('退款失败');
			}
				
			$res = $this->distributorInfoDao()->depleteFrozenMoney($info['distributor_id'], $info['money']);
			if (!$res) {
				M()->rollback();
				throw new \Exception('退款失败');
			}
				
			//记录退款日志
			$params = array(
					'distributor_id'=>$distributor['distributor_id'],
					'money'=>$distributor['money'],
					'money'=>$info['money'],
					'apply_id'=>$info['apply_id'],
			);
			$res = $this->distributorAccountDao()->backCashApplyMoney($params);
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
		
		$map=array('apply_id'=>$params['apply_id'],'status'=>Status::CashAgree);
		$info=$this->distributorCashApplyDao()->findRecord($map);
		if(empty($info)){
			throw new \Exception('提现记录不存在');
		}
		if(in_array($params['status'],$limit_array)===false){
			throw new \Exception('状态错误');
		}
		
		M()->startTrans();
		
		$data = array(
				'apply_id'=>$params['apply_id'],
				'certify'=>$params['certify'],
				'status'=>$params['status'],
		);
		$result = $this->distributorCashApplyDao()->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('打款失败');
		}
		
		//扣除冻结金额
		$result = $this->distributorInfoDao()->depleteFrozenMoney($info['distributor_id'], $info['money']);
		if($result < 1) {
			M()->rollback();
			throw new \Exception('打款失败');
		}
		
		//发送短信
		$distributor_info = $this->distributorInfoDao()->getRecord($info['distributor_id']);
		$data = array(
				'DistributorName'=>$distributor_info['distributor_name'],
				'Date'=>date('Y年m月d日 H:i'),
				'Money'=>$info['money'],
				'DistributorMoney'=>$distributor_info['money'],
				'DistributorId'=>$distributor_info['distributor_id'],
				'Title'=>'提现通知',
				'MsgType'=>Status::MsgTypeDistributorCash,
				'ApplyId'=>$info['apply_id'],
		);
		try {
			$this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnDistributorCash ,$distributor_info['mobile'], $data);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		M()->commit();
	}
	
	public function delete($id){
		$result = $this->distributorCashApplyDao()->delRecord($id);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getAdminCount(){
		$map = array('a.status'=>0);
		$wait = $this->distributorCashApplyDao()->searchRecordsCount($map);
		return array(
				'wait'=>$wait,
		);
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (!empty($params['keyword'])) {
			$where['distributor_name'] = array('like', '%'.$params['keyword'].'%');
			$distributor_list = $this->distributorInfoDao()->getFieldRecord($where);
			if (empty($distributor_list)) {
				return array();
			}
			$distributor_ids = array_keys($distributor_list);
			$map['distributor_id'] = array('in', $distributor_ids);
		}
		if (!empty($params['apply_id'])) {
			$map['apply_id'] = array('like', '%'.$params['apply_id'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if(isset($params['status'])){
			$map['status'] = $params['status'];
		}
		
		$count = $this->distributorCashApplyDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'apply_id DESC' : $params['orderby'];
			$list = $this->distributorCashApplyDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$distributor_ids[] = $v['distributor_id'];
			}
			$map['distributor_id'] = array('in', $distributor_ids);
			$distributors = $this->distributorInfoDao()->searchAllRecords($map);
			
			foreach ($list as $k => $v) {
				//店铺
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']]['distributor_name'];
				$list[$k]['distributor_image'] = $distributors[$v['distributor_id']]['distributor_image'];
				//省市区
				$list[$k]['region_name'] = $this->regionDao()->getRegionName($v['region_code']);
				//状态
				$list[$k]['status_label'] = Status::$cashStatusList[$v['status']];
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
	
	public function distributorCashApplyDao(){
		return D('Common/Distributor/CashApply');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function distributorAccountDao(){
		return D('Common/Distributor/Account');
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
	
	public function bankDao(){
		return D('Common/Bank');
	}
	
	private function smsLogic(){
		return D('Sms', 'Logic');
	}
}