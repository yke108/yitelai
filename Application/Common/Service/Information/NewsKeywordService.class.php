<?php
namespace Common\Service\Information;

class NewsKeywordService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsKeywordDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsKeywordDao = $this->newsKeywordDao();
		if (!$newsKeywordDao->create($data)){
			 throw new \Exception($newsKeywordDao->getError());
		}
		if ($params['keyword_id'] > 0){
			$result = $newsKeywordDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsKeywordDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsKeywordDao()->delRecord($id);
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
		
		$newsKeywordDao = $this->newsKeywordDao();
		$count = $newsKeywordDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'keyword_id desc' : $params['orderby'];
			$list = $newsKeywordDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($map, $order_by){
		$newsKeywordDao = $this->newsKeywordDao();
		$orderby = $order_by ? $order_by : 'keyword_id DESC';
		return $newsKeywordDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function newsKeywordDao(){
		//返回model
		return D('Common/Information/News/Keyword');
	}
}//end HelpService!甜品