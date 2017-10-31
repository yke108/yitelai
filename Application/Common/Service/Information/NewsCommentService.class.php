<?php
namespace Common\Service\Information;

class NewsCommentService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsCommentDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsCommentDao = $this->newsCommentDao();
		if (!$newsCommentDao->create($data)){
			 throw new \Exception($newsCommentDao->getError());
		}
		if ($params['comment_id'] > 0){
			$result = $newsCommentDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			M()->startTrans();
			
			$comment_id = $newsCommentDao->add();
			if ($comment_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			$result = $this->newsInfoDao()->where(array('news_id'=>$params['news_id']))->setInc('comment_count');
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			M()->commit();
			
			return array('comment_id'=>$comment_id);
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsCommentDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoOpen($info){
		$status = $info['status'] == 1 ? 2 : 1;
		$result = $this->newsCommentDao()->where(array('comment_id'=>$info['comment_id']))->save(array('status'=>$status));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['news_id'])) {
			$map['news_id'] = $params['news_id'];
		}
		if (isset($params['comment_id'])) {
			$map['comment_id'] = $params['comment_id'];
		}
		
		$newsCommentDao = $this->newsCommentDao();
		$count = $newsCommentDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'comment_id desc' : $params['orderby'];
			$list = $newsCommentDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list, $params['user_id']),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsCommentDao = $this->newsCommentDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $newsCommentDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list, $user_id = 0){
		if (!empty($list)) {
			$user_ids = $comment_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$comment_ids[] = $v['comment_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			if ($user_id > 0) {
				$map = array(
						'comment_id'=>array('in', $comment_ids),
						'user_id'=>$user_id,
				);
				$likes = $this->newsCommentLikeDao()->searchAllRecords($map);
			}
			
			foreach ($list as $k => $v) {
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl($users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
			}
		}
		
		return $list;
	}
	
	private function newsCommentDao(){
		return D('Common/Information/News/Comment');
	}
	
	private function newsInfoDao(){
		return D('Common/Information/News/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function newsCommentLikeDao(){
		return D('Common/Information/News/CommentLike');
	}
}//end HelpService!甜品