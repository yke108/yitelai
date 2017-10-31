<?php
namespace Common\Service;

class MerchantLogService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->merchantLogDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->merchantLogDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->merchantLogDao()->create($data)){
			 throw new \Exception($this->merchantLogDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['log_id'] > 0){
			$result = $this->merchantLogDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$log_id = $this->merchantLogDao()->add();
			if ($log_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($id){
		//如果分销商有设计师，提示先删除设计师
		$designer = $this->designerInfoDao()->findRecord(array('log_id'=>$id));
		if ($designer) {
			throw new \Exception('请先删除设计师');
		}
		
		$result = $this->merchantLogDao()->delRecord($id);
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
		
		$count = $this->merchantLogDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$list = $this->merchantLogDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map, $orderby){
		$orderby = empty($orderby) ? 'log_id DESC' : $orderby;
		$list = $this->merchantLogDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($log_id){
		$info = $this->merchantLogDao()->find($log_id);
		if (empty($info)) throw new \Exception('数据不存在');
		$is_show = $info['is_show'] == 1 ? 0 : 1;
		$result = $this->merchantLogDao()->where(array('log_id'=>$log_id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k=> $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			;
		}
		
		return $info;
	}
	
	private function merchantLogDao(){
		return D('Common/Merchant/MerchantLog');
	}
}//end HelpService!甜品