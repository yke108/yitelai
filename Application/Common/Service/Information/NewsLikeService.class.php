<?php
namespace Common\Service\Information;

class NewsLikeService{
	public function getInfo($like_id){
		if ($like_id < 1) return false;
		return $this->newsLikeDao()->getRecord($like_id);
	}
	
	public function findInfo($map){
		return $this->newsLikeDao()->findRecord($map);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsLikeDao = $this->newsLikeDao();
		if (!$newsLikeDao->create($data)){
			 throw new \Exception($newsLikeDao->getError());
		}
		if ($params['like_id'] > 0){
			$result = $newsLikeDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			M()->startTrans();
			
			$result = $newsLikeDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			$result = $this->newsInfoDao()->where(array('news_id'=>$params['news_id']))->setInc('like_count');
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			M()->commit();
		}
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (isset($params['status'])) {
			$map['status'] = $params['status'];
		}
		
		$newsLikeDao = $this->newsLikeDao();
		$count = $newsLikeDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'like_id desc' : $params['orderby'];
			$list = $newsLikeDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsLikeDao = $this->newsLikeDao();
		$orderby = empty($params['orderby']) ? 'like_id DESC' : $params['orderby'];
		return $newsLikeDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function newsLikeDao(){
		return D('Common/Information/News/Like');
	}
	
	private function newsInfoDao(){
		return D('Common/Information/News/Info');
	}
}//end HelpService!甜品