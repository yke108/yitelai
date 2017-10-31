<?php
namespace Common\Service;

class CookMaterialService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->cookMaterialDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$cookMaterialDao = $this->cookMaterialDao();
		if (!$cookMaterialDao->create($data)){
			 throw new \Exception($cookMaterialDao->getError());
		}
		
		if ($params['material_id'] > 0){
			$result = $cookMaterialDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $cookMaterialDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->cookMaterialDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->cookMaterialDao()->where(array('material_id'=>$info['material_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		
		$cookMaterialDao = $this->cookMaterialDao();
		$count = $cookMaterialDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $cookMaterialDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$cookMaterialDao = $this->cookMaterialDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $cookMaterialDao->searchAllRecords($params['map'], $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function cookMaterialDao(){
		//返回model
		return D('Common/Cook/Material');
	}
}//end HelpService!甜品