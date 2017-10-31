<?php
namespace Common\Service\Information;

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
		
		$cookCommentDao = $this->cookCommentDao();
		if (!$cookCommentDao->create($data)){
			 throw new \Exception($cookCommentDao->getError());
		}
		if ($params['comment_id'] > 0){
			$result = $cookCommentDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $cookCommentDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
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
				$list[$k]['name'] = $books[$v['book_id']]['name'];
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
			}
		}
		
		return $list;
	}
	
	private function cookCommentDao(){
		return D('Common/Information/Cook/Comment');
	}
	
	private function cookBookDao(){
		return D('Common/Information/Cook/Book');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品