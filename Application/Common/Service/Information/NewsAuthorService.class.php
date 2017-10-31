<?php
namespace Common\Service\Information;

class NewsAuthorService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsAuthorDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsAuthorDao = $this->newsAuthorDao();
		if (!$newsAuthorDao->create($data)){
			 throw new \Exception($newsAuthorDao->getError());
		}
		if ($params['author_id'] > 0){
			$result = $newsAuthorDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsAuthorDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsAuthorDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
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
		
		$newsAuthorDao = $this->newsAuthorDao();
		$count = $newsAuthorDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'author_id desc' : $params['orderby'];
			$list = $newsAuthorDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsAuthorDao = $this->newsAuthorDao();
		$orderby = empty($params['orderby']) ? 'author_id DESC' : $params['orderby'];
		return $newsAuthorDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function newsAuthorDao(){
		//返回model
		return D('Common/Information/News/Author');
	}
}//end HelpService!甜品