<?php
namespace Common\Service;

class GoodsLabelService{
	protected $goodsLabel;
	
	public function __construct(){
		$this->goodsLabel = D('Common/Goods/GoodsLabel');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->goodsLabel->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->goodsLabel->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->goodsLabel->create($data)){
			 throw new \Exception($this->goodsLabel->getError());
		}
		if ($params['label_id'] > 0){
			$result = $this->goodsLabel->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsLabel->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->goodsLabel->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function del($map){
		$result = $this->goodsLabel->where($map)->delete();
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['nav_show'])) {
			$map['nav_show'] = $params['nav_show'];
		}
		
		$count = $this->goodsLabel->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, label_id DESC' : $params['orderby'];
			$list = $this->goodsLabel->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->goodsLabel->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		return $list;
	}
}//end HelpService!甜品