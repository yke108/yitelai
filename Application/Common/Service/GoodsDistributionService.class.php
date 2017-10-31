<?php
namespace Common\Service;

class goodsDistributionService{
	protected $goodsDistribution;
	
	public function __construct(){
		$this->goodsDistribution = D('Common/Goods/GoodsDistribution');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->goodsDistribution->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->goodsDistribution->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->goodsDistribution->create($data)){
			 throw new \Exception($this->goodsDistribution->getError());
		}
		if ($params['distribution_id'] > 0){
			$result = $this->goodsDistribution->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsDistribution->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->goodsDistribution->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function del($map){
		$result = $this->goodsDistribution->where($map)->delete();
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
		
		$count = $this->goodsDistribution->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'distribution_id ASC' : $params['orderby'];
			$list = $this->goodsDistribution->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->goodsDistribution->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		return $list;
	}
}//end HelpService!甜品