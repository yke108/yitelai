<?php
namespace Common\Service\Information;

class NewsCommentLikeService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsCommentLikeDao()->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->newsCommentLikeDao()->findRecord($map);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsCommentLikeDao = $this->newsCommentLikeDao();
		if (!$newsCommentLikeDao->create($data)){
			 throw new \Exception($newsCommentLikeDao->getError());
		}
		if ($params['like_id'] > 0){
			$result = $newsCommentLikeDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			M()->startTrans();
			
			$result = $newsCommentLikeDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			$result = $this->newsCommentDao()->where(array('comment_id'=>$params['comment_id']))->setInc('like_count');
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
				
			M()->commit();
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsCommentLikeDao()->delRecord($id);
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
		
		$newsCommentLikeDao = $this->newsCommentLikeDao();
		$count = $newsCommentLikeDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'like_id desc' : $params['orderby'];
			$list = $newsCommentLikeDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsCommentLikeDao = $this->newsCommentLikeDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $newsCommentLikeDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function newsCommentLikeDao(){
		return D('Common/Information/News/CommentLike');
	}
	
	private function newsCommentDao(){
		return D('Common/Information/News/Comment');
	}
}//end HelpService!甜品