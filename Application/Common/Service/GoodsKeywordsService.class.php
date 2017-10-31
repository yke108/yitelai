<?php
namespace Common\Service;
use Common\Basic\Pager;

class GoodsKeywordsService{
	private $goodsKeywordsDao;
	
	public function __construct(){
		$this->goodsKeywordsDao = D('Common/GoodsKeywords');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->goodsKeywordsDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		$lnglat = explode(',', $data['lnglat']);
		$data['longitude'] = $lnglat[0];
		$data['latitude'] = $lnglat[1];
		if (!$this->goodsKeywordsDao->create($data)){
			 throw new \Exception($this->goodsKeywordsDao->getError());
		}
		
		M()->startTrans();
		
		if ($data['search_id'] > 0){
			$result = $this->goodsKeywordsDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsKeywordsDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		return true;
	}
	
	public function delete($id){
		M()->startTrans();
		$result = $this->goodsKeywordsDao->delRecord($id);
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
		
		$map = array();
		if (!empty($params['get']['ref_type'])) {
			$map['ref_type'] = $params['get']['ref_type'];
		}
		if (!empty($params['get']['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['get']['start_time']));
		}
		if (!empty($params['get']['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['get']['end_time']) + 86400);
		}
		
		$count = $this->goodsKeywordsDao->searchRecordsCount($map);
		$pager = new Pager($count, $params['pagesize']);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, search_id DESC' : $params['orderby'];
			$list = $this->goodsKeywordsDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
			'pager'=>$pager->show(),
		);
	}
	
	private function outputForList($list) {
		return $list;
	}
	
	private function outputForInfo($info) {
		return $info;
	}
}//end HelpService!甜品