<?php
namespace Common\Service;
use Common\Basic\Status;

class MerchantService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->merchantDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->merchantDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function searchInfo($map){
		$info = $this->merchantDao()->where($map)->order('merchant_id DESC')->find();
		return $this->outputForInfo($info);
	}
	
	public function updateInfo($map, $data){
		$result = $this->merchantDao()->where($map)->save($data);
		if ($result === false) throw new \Exception('操作失败');
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		M()->startTrans();
		
		if ($data['merchant_id'] > 0){
			if (!$this->merchantDao()->create($data)){
				throw new \Exception($this->merchantDao()->getError());
			}
			
			if ($params['license_type'] == '普通执照') {
				if (isset($params['organization_no']) && empty($params['organization_no'])) throw new \Exception('组织机构代码不能为空');
				if (isset($params['organization_period_start']) && empty($params['organization_period_start']) && empty($params['organization_period_long'])) throw new \Exception('组织机构代码有效期不能为空');
				if (isset($params['organization_period_end']) && empty($params['organization_period_end']) && empty($params['organization_period_long'])) throw new \Exception('组织机构代码有效期不能为空');
				if (isset($params['organization_pic']) && empty($params['organization_pic'])) throw new \Exception('组织机构代码电证子版不能为空');
			}
			
			$result = $this->merchantDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			//不能重复提交申请
			$map = array(
					'user_id'=>$data['user_id'],
					'status'=>array('in', array(Status::MerchantStatusOn, Status::MerchantStatusPass))
			);
			$info = $this->findInfo($map);
			if ($info) throw new \Exception('不能重复提交申请');
			
			$data['merchant_id'] = date('ymdHis').rand(1000,9999);
			$data['add_time'] = NOW_TIME;
			if (!$this->merchantDao()->create($data)){
				throw new \Exception($this->merchantDao()->getError());
			}
			
			if ($params['license_type'] == '普通执照') {
				if (isset($params['organization_no']) && empty($params['organization_no'])) throw new \Exception('组织机构代码不能为空');
				if (isset($params['organization_period_start']) && empty($params['organization_period_start']) && empty($params['organization_period_long'])) throw new \Exception('组织机构代码有效期不能为空');
				if (isset($params['organization_period_end']) && empty($params['organization_period_end']) && empty($params['organization_period_long'])) throw new \Exception('组织机构代码有效期不能为空');
				if (isset($params['organization_pic']) && empty($params['organization_pic'])) throw new \Exception('组织机构代码电证子版不能为空');
			}
			
			$merchant_id = $this->merchantDao()->add();
			if ($merchant_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($id){
		$result = $this->merchantDao()->delRecord($id);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['keyword'])) {
			$map['distributor_name'] = array('like', '%'.$params['keyword'].'%');
		}
		
		$count = $this->merchantDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'merchant_id DESC' : $params['orderby'];
			$list = $this->merchantDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map, $orderby){
		$orderby = empty($orderby) ? 'merchant_id DESC' : $orderby;
		$list = $this->merchantDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($merchant_id){
		$info = $this->merchantDao()->find($merchant_id);
		if (empty($info)) throw new \Exception('数据不存在');
		$is_show = $info['is_show'] == 1 ? 0 : 1;
		$result = $this->merchantDao()->where(array('merchant_id'=>$merchant_id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$user_ids = $type_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$type_ids[] = $v['type_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$types = $this->distributorTypeDao()->getTypesByIds($type_ids);
			
			foreach ($list as $k=> $v) {
				//会员信息
				$list[$k]['user_name'] = $users[$v['user_id']]['real_name'] ? $users[$v['user_id']]['real_name'] : $users[$v['user_id']]['nick_name'];
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
				switch ($v['status']) {
					case Status::MerchantStatusOn: $status_label = '申请中'; break;
					case Status::MerchantStatusPass: $status_label = '申请通过'; break;
					case Status::MerchantStatusNoPass: $status_label = '申请不通过'; break;
					case Status::MerchantStatusCancel: $status_label = '撤消申请'; break;
					default: $status_label = ''; break;
				}
				$list[$k]['status_label'] = $status_label;
				
				//保证金和服务费
				$list[$k]['deposit'] = $types[$v['type_id']]['deposit'];
				$list[$k]['deposit_pay_label'] = ($v['deposit_pay'] == 1) ? '已支付' : '未支付';
				$list[$k]['service_charge'] = $types[$v['type_id']]['service_charge'];
				$list[$k]['service_charge_pay_label'] = ($v['service_charge_pay'] == 1) ? '已支付' : '未支付';
				$list[$k]['platform_take'] = $types[$v['type_id']]['platform_take'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			$distributor_type_top = $this->distributorTypeDao()->getRecord($info['top_id']);
			$distributor_type = $this->distributorTypeDao()->getRecord($info['type_id']);
			//期望店铺类型
			$info['type_name_top'] = $distributor_type_top['type_name'];
			$info['type_name'] = $distributor_type['type_name'];
			
			//主营类目
			$cat = $this->goodsCatDao()->getRecord($info['cat_id']);
			$info['cat_name'] = $cat['cat_name'];
			
			//保证金和服务费
			$info['deposit'] = $distributor_type['deposit'];
			$info['service_charge'] = $distributor_type['service_charge'];
			$info['platform_take'] = $distributor_type['platform_take'];
			
			//地区
			$info['license_region_name'] = $this->regionDao()->getRegionName($info['license_region']);
			$info['company_region_name'] = $this->regionDao()->getRegionName($info['company_region']);
			$info['bank_branch_region_name'] = $this->regionDao()->getRegionName($info['bank_branch_region']);
		}
		
		return $info;
	}
	
	public function payDeposit($params){
		//判断余额是否足够
		$user_info = $this->userInfoDao()->getRecord($params['user_id']);
		if ($params['pay_id'] == 1) {
			if ($params['deposit'] > $user_info['user_money']) throw new \Exception('余额不足');
		}
		
		M()->startTrans();
		
		//减少账户余额
		$result = $this->userInfoDao()->depleteMoney($user_info['user_id'], $params['deposit']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//账户记录
		$params_account = array(
				'user_id'=>$user_info['user_id'],
				'amount_old'=>$user_info['user_money'],
				'amount_change'=>$params['deposit'],
				'ref_id'=>$params['merchant_id'],
		);
		$result = $this->userAccountDao()->payDeposit($params_account);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//支付成功
		$result = $this->payDepositSuccess($params);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function payDepositSuccess($params) {
		//修改支付状态
		$map = array('merchant_id'=>$params['merchant_id']);
		$data = array('deposit'=>$params['deposit'], 'deposit_pay'=>1, 'deposit_pay_time'=>NOW_TIME);
		$merchant_info = $this->getInfo($params['merchant_id']);
		if ( $merchant_info['service_charge_pay'] == 1 ) {
			$data['step'] = 6;
		}
		$result = $this->merchantDao()->updateRecord($map, $data);
		if ($result ===  false) {
			return false;
		}
		return true;
	}
	
	public function payService($params){
		//判断余额是否足够
		$user_info = $this->userInfoDao()->getRecord($params['user_id']);
		if ($params['pay_id'] == 1) {
			if ($params['service_charge'] > $user_info['user_money']) throw new \Exception('余额不足');
		}
		
		M()->startTrans();
		
		//减少账户余额
		$result = $this->userInfoDao()->depleteMoney($user_info['user_id'], $params['service_charge']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//账户记录
		$params_account = array(
				'user_id'=>$user_info['user_id'],
				'amount_old'=>$user_info['user_money'],
				'amount_change'=>$params['service_charge'],
				'ref_id'=>$params['merchant_id'],
		);
		$result = $this->userAccountDao()->payService($params_account);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//支付成功
		$result = $this->payServiceSuccess($params);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function payServiceSuccess($params) {
		//修改支付状态
		$map = array('merchant_id'=>$params['merchant_id']);
		$data = array('service_charge'=>$params['service_charge'], 'service_charge_pay'=>1, 'service_charge_pay_time'=>NOW_TIME);
		$merchant_info = $this->getInfo($params['merchant_id']);
		if ( $merchant_info['deposit_pay'] == 1 ) {
			$data['step'] = 6;
		}
		$result = $this->merchantDao()->updateRecord($map, $data);
		if ($result ===  false) {
			return false;
		}
		return true;
	}
	
	private function merchantDao(){
		return D('Common/Merchant/Merchant');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function distributorTypeDao(){
		return D('Common/Distributor/Type');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function merchantLogDao(){
		return D('Common/Merchant/MerchantLog');
	}
	
	private function goodsCatDao(){
		return D('Common/Goods/GoodsCat');
	}
	
	private function regionDao(){
		return D('Region');
	}
}//end HelpService!甜品