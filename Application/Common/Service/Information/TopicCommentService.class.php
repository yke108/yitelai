<?php
namespace Common\Service\Information;

class TopicCommentService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->topicCommentDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if($params['subject_id'] > 0){
			$data['subject_id'] = $params['subject_id'];
		}
		$topicCommentDao = $this->topicCommentDao();
		if (!$topicCommentDao->create($data)){
			 throw new \Exception($topicCommentDao->getError());
		}
		if ($params['subject_id'] > 0){
			$result = $topicCommentDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $topicCommentDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->topicCommentDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->topicCommentDao()->where(array('subject_id'=>$info['subject_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->topicCommentDao()->where(array('subject_id'=>$info['subject_id']))->save(array('is_open'=>$is_open));
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
		if (isset($params['status'])) {
			$map['status'] = $params['status'];
		}
		
		$topicCommentDao = $this->topicCommentDao();
		$count = $topicCommentDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'comment_id desc' : $params['orderby'];
			$list = $topicCommentDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = array();
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		$topicCommentDao = $this->topicCommentDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $topicCommentDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function topicCommentDao(){
		//返回model
		return D('Common/Information/Community/Comment');
	}
}//end HelpService!甜品