<?php
namespace Common\Service;

class GoodsSceneService{
	protected $goodsScene;
	
	public function __construct(){
		$this->goodsScene = D('Common/Goods/GoodsScene');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->goodsScene->getRecord($id);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->goodsScene->create($data)){
			 throw new \Exception($this->goodsScene->getError());
		}
		if ($params['scene_id'] > 0){
			$result = $this->goodsScene->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsScene->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->goodsScene->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$count = $this->goodsScene->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, scene_id DESC' : $params['orderby'];
			$list = $this->goodsScene->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->goodsScene->searchAllRecords($map);
	}
	
	public function selectAllList($map = array()){
		return $this->goodsScene->selectAllRecords($map);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		return $list;
	}
}//end HelpService!甜品