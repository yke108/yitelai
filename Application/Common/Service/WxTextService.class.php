<?php
namespace Common\Service;

class WxTextService{
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->wxTextDao()->getRecord($id);
	}
	
	public function createOrModify($params){
		//参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$wxTextDao = $this->wxTextDao();
		if (!$wxTextDao->create($data)){
			 throw new \Exception($wxTextDao->getError());
		}
		if ($params['text_id'] > 0){
			$result = $wxTextDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $wxTextDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
		return $result;
	}
	
	public function textDelete($id){
		$result = $this->wxTextDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$wxTextDao = $this->wxTextDao();
		$count = $wxTextDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'text_id DESC' : $params['orderby'];
			$list = $wxTextDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outPutForList($list),
			'count'=>$count,
		);
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			
		}
		return $list;
	}
	
	//调用model
	private function wxTextDao(){
		return D('Common/Weixin/WxText');
	}
}//end FeedbackService!