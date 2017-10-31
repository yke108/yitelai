<?php
namespace Common\Service\Information;

class NewsSourceService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsSrourceDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsSrourceDao = $this->newsSrourceDao();
		if (!$newsSrourceDao->create($data)){
			 throw new \Exception($newsSrourceDao->getError());
		}
		if ($params['source_id'] > 0){
			$result = $newsSrourceDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsSrourceDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsSrourceDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['source_name'] = array('like', '%'.$params['keyword'].'%');
		}
		
		$newsSrourceDao = $this->newsSrourceDao();
		$count = $newsSrourceDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'source_id desc' : $params['orderby'];
			$list = $newsSrourceDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsSrourceDao = $this->newsSrourceDao();
		$orderby = empty($params['orderby']) ? 'source_id DESC' : $params['orderby'];
		return $newsSrourceDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function newsSrourceDao(){
		//返回model
		return D('Common/Information/News/Source');
	}
}//end HelpService!甜品