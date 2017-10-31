<?php
namespace Common\Service;

class CookCommentService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->cookCommentDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['pictures'] = trim($data['pictures'], ',');
		
		$cookCommentDao = $this->cookCommentDao();
		if (!$cookCommentDao->create($data)){
			 throw new \Exception($cookCommentDao->getError());
		}
		
		M()->startTrans();
		
		if ($params['comment_id'] > 0){
			$result = $cookCommentDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$result = $cookCommentDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//统计评论数
			if ($this->cookBookDao()->where(array('book_id'=>$params['book_id']))->setInc('comment_count') === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		M()->commit();
	}
	
	public function infoDelete($id){
		$result = $this->cookCommentDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoOpen($info){
		$status = $info['status'] == 1 ? 2 : 1;
		$result = $this->cookCommentDao()->where(array('comment_id'=>$info['comment_id']))->save(array('status'=>$status));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoLike($comment_id){
		$result = $this->cookCommentDao()->where(array('comment_id'=>$comment_id))->setInc('like_count');
		if ($result === false) throw new \Exception('点赞失败');
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
		if (isset($params['book_id'])) {
			$map['book_id'] = $params['book_id'];
		}
		
		$cookCommentDao = $this->cookCommentDao();
		$count = $cookCommentDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'comment_id desc' : $params['orderby'];
			$list = $cookCommentDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$cookCommentDao = $this->cookCommentDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $cookCommentDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = $book_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$book_ids[] = $v['book_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$books = $this->cookBookDao()->getBooksByIds($book_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['pictures'] = $v['pictures'] ? explode(',', $v['pictures']) : array();
				
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
				
				$list[$k]['book_name'] = $books[$v['book_id']]['name'];
			}
		}
		
		return $list;
	}
	
	private function cookCommentDao(){
		return D('Common/Cook/Comment');
	}
	
	private function cookBookDao(){
		return D('Common/Cook/Book');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品