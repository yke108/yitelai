<?php
namespace Common\Service\Distributor;

use Common\Basic\Status;
class FineService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		$info = $this->distributorFineDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->distributorFineDao()->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function createFine($params){
		if (empty($params['point']) && empty($params['money'])) throw new \Exception('扣分和罚款不能同时为空');
		
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['fine_id'] = date('ymdHis').rand(1000,9999);
		$data['add_time'] = NOW_TIME;
		
		if (!$this->distributorFineDao()->create($data)) throw new \Exception($this->distributorFineDao()->getError());
		
		M()->startTrans();
		
		$fine_id = $this->distributorFineDao()->add();
		if ($fine_id < 1){
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		//扣分
		if ($params['point']) {
			$result = $this->distributorInfoDao()->where(array('distributor_id'=>$params['distributor_id']))->setDec('point', $params['point']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		//罚款
		if ($params['money']) {
			$result = $this->distributorInfoDao()->where(array('distributor_id'=>$params['distributor_id']))->setDec('money', $params['money']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		//创建日志
		$data = array(
				'ref_id'=>$params['ref_id'],
				'ref_type'=>$params['ref_type'],
				'fine_id'=>$fine_id,
				'content'=>'发布罚款',
				'add_time'=>NOW_TIME,
		);
		$result = $this->distributorFineLogDao()->add($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	public function check($params) {
		if (!in_array($params['status'], array(Status::FineStatusAgree, Status::FineStatusCancel))) throw new \Exception('状态不正确');
		
		$fine_info = $this->distributorFineDao()->getRecord($params['fine_id']);
		if (empty($fine_info)) throw new \Exception('罚款不存在');
		if ($fine_info['status'] != Status::FineStatusAppeal) throw new \Exception('罚款已审核');
		
		M()->startTrans();
		
		//修改罚款状态
		$data = array(
				'fine_id'=>$params['fine_id'],
				'status'=>$params['status'],
		);
		$result = $this->distributorFineDao()->saveRecord($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//创建日志
		$data = array(
				'ref_id'=>$params['admin_id'],
				'ref_type'=>Status::RefTypeAdmin,
				'content'=>Status::$fineStatusList[$params['status']],
				'fine_id'=>$params['fine_id'],
				'add_time'=>NOW_TIME,
		);
		$result = $this->distributorFineLogDao()->add($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//撤消罚款
		if ($params['status'] == Status::FineStatusCancel) {
			if ($fine_info['point'] > 0) {
				$result = $this->distributorInfoDao()->where(array('distributor_id'=>$fine_info['distributor_id']))->setInc('point', $fine_info['point']);
				if ($result === false){
					M()->rollback();
					throw new \Exception('系统错误');
				}
			}
			
			if ($fine_info['money'] > 0) {
				$result = $this->distributorInfoDao()->where(array('distributor_id'=>$fine_info['distributor_id']))->setInc('money', $fine_info['money']);
				if ($result === false){
					M()->rollback();
					throw new \Exception('系统错误');
				}
			}
		}
		
		M()->commit();
	}
	
	public function delete($fine_id){
		$result = $this->distributorFineDao()->delRecord($fine_id);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if ($params['status']) {
			$map['status'] = $params['status'];
		}
		
		$count = $this->distributorFineDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'fine_id DESC' : $params['orderby'];
			$list = $this->distributorFineDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
		);
	}
	
	public function getAllList($map){
		return $this->distributorFineDao()->searchAllRecords($map, $orderby = 'fine_id DESC');
	}
	
	public function logAllList($params){
		if ($params['fine_id']) {
			$map['fine_id'] = $params['fine_id'];
		}
		
		$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
		$list = $this->distributorFineLogDao()->searchAllRecords($map, $orderby);
		
		if (!empty($list)) {
			foreach ($list as $v) {
				if ($v['ref_type'] == Status::RefTypeAdmin) {
					$admin_ids[] = $v['ref_id'];
				}
			}
			$admins = $this->adminInfoDao()->getRecords($admin_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['admin_name'] = $admins[$v['ref_id']]['admin_name'];
				$list[$k]['avatar'] = $admins[$v['ref_id']]['avatar'];
			}
		}
		
		return $list;
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$distributor_ids = array();
			foreach ($list as $v) {
				$distributor_ids[] = $v['distributor_id'];
			}
			//店铺
			$map = array(
					'distributor_id'=>array('in', $distributor_ids),
			);
			$distributors = $this->distributorInfoDao()->getFieldRecord($map);
			//罚款类型
			$types = $this->distributorFineTypeDao()->searchFieldRecords();
			
			foreach ($list as $k => $v) {
				//状态
				$list[$k]['status_label'] = Status::$fineStatusList[$v['status']];
				$list[$k]['type_name'] = $types[$v['type_id']];
				//店铺
				$list[$k]['distributor_image'] = $distributors[$v['distributor_id']]['distributor_image'];
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']]['distributor_name'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			//状态
			$info['status_label'] = Status::$fineStatusList[$info['status']];
			$info['pictures'] = $info['pictures'] ? explode(',', $info['pictures']) : array();
			//店铺
			$distributor_info = $this->distributorInfoDao()->getRecord($info['distributor_id']);
			$info['distributor_image'] = $distributor_info['distributor_image'];
			$info['distributor_name'] = $distributor_info['distributor_name'];
		}
		
		return $info;
	}
	
	private function distributorFineDao() {
		return D('Common/Distributor/Fine');
	}
	
	private function distributorInfoDao() {
		return D('Common/Distributor/Info');
	}
	
	private function distributorFineLogDao() {
		return D('Common/Distributor/FineLog');
	}
	
	private function distributorFineTypeDao() {
		return D('Common/Distributor/FineType');
	}
	
	private function adminInfoDao() {
		return D('Common/Admin/AdminInfo');
	}
}//end HelpService!甜品