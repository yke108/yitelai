<?php
namespace Common\Service;

class GoodsServiceService{
	protected $goodsService;
	
	public function __construct(){
		$this->goodsService = D('Common/Goods/GoodsService');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->goodsService->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->goodsService->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->goodsService->create($data)){
			 throw new \Exception($this->goodsService->getError());
		}
		if ($params['service_id'] > 0){
			$result = $this->goodsService->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsService->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->goodsService->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function del($map){
		$result = $this->goodsService->where($map)->delete();
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if ($params['distributor_id']) {
			$map['distributor_id'] = array('in', array(0, $params['distributor_id']));
		}else {
			$map['distributor_id'] = 0;
		}
		
		$count = $this->goodsService->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'service_id ASC' : $params['orderby'];
			$list = $this->goodsService->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->goodsService->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		return $list;
	}
}//end HelpService!甜品