<?php
namespace Common\Service;

class ActivityCommentService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->activityCommentDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$activityCommentDao = $this->activityCommentDao();
		if (!$activityCommentDao->create($data)){
			 throw new \Exception($activityCommentDao->getError());
		}
		
		if ($params['comment_id'] > 0){
			$result = $activityCommentDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			M()->startTrans();
			
			$comment_id = $activityCommentDao->add();
			if ($comment_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//统计评论数
			if ($this->activityInfoDao()->where(array('activity_id'=>$params['activity_id']))->setInc('comment_count') === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
			
			M()->commit();
			
			return $this->getInfo($comment_id);
		}
	}
	
	public function infoDelete($id){
		$result = $this->activityCommentDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->activityCommentDao()->where(array('comment_id'=>$info['comment_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->activityCommentDao()->where(array('comment_id'=>$info['comment_id']))->save(array('is_open'=>$is_open));
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
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		
		$activityCommentDao = $this->activityCommentDao();
		$count = $activityCommentDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'comment_id desc' : $params['orderby'];
			$list = $activityCommentDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$activityCommentDao = $this->activityCommentDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $activityCommentDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = $book_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$activity_ids[] = $v['activity_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$activitys = $this->activityInfoDao()->getRecordsField($activity_ids);
			
			foreach ($list as $k => $v) {
				//活动
				$list[$k]['title'] = $activitys[$v['activity_id']]['title'];
				$list[$k]['pictures'] = $v['pictures'] ? explode('#', $v['pictures']) : array();
				//用户
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			$info['pictures'] = $info['pictures'] ? explode('#', $info['pictures']) : array();
			
			//用户
			$user_info = $this->userInfoDao()->getRecord($info['user_id']);
			$info['nick_name'] = $user_info['nick_name'];
			$info['user_img'] = $user_info['user_img'];
		}
		
		return $info;
	}
	
	//返回model
	private function activityCommentDao(){
		return D('Common/ActivityInfo/Comment');
	}
	
	private function activityInfoDao(){
		return D('Common/ActivityInfo/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品