<?php
namespace Common\Service;

class WxNewsService{
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->wxNewsDao()->getRecord($id);
	}
	
	public function createOrModify($params){
		//参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$wxNewsDao = $this->wxNewsDao();
		if (!$wxNewsDao->create($data)){
			 throw new \Exception($wxNewsDao->getError());
		}
		if ($params['news_id'] > 0){
			$result = $wxNewsDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $wxNewsDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
		return $result;
	}
	
	public function newsDelete($id){
		$result = $this->wxNewsDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$wxNewsDao = $this->wxNewsDao();
		$count = $wxNewsDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'news_id DESC' : $params['orderby'];
			$list = $wxNewsDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outPutForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map){
		$list = $this->wxNewsDao()->searchAllRecords($map);
		return $this->outPutForList($list);
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			
		}
		return $list;
	}
	
	//调用model
	private function wxNewsDao(){
		return D('Common/Weixin/WxNews');
	}
}//end FeedbackService!