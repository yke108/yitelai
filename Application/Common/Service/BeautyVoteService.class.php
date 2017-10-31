<?php
namespace Common\Service;

class BeautyVoteService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->beautyVoteDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$beautyVoteDao = $this->beautyVoteDao();
		if (!$beautyVoteDao->create($data)){
			 throw new \Exception($beautyVoteDao->getError());
		}
		
		if ($params['vote_id'] > 0){
			$result = $beautyVoteDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$map = array('user_id'=>$params['user_id'], 'beauty_id'=>$params['beauty_id']);
			$vote_info = $this->beautyVoteDao()->where($map)->find();
			if ($vote_info) throw new \Exception('您已经投票过，不能重复投票');
			
			M()->startTrans();
			
			$vote_id = $beautyVoteDao->add();
			if ($vote_id < 1){
				M()->rollback();
				
			}
			
			//统计评论数
			if ($this->beautyInfoDao()->where(array('beauty_id'=>$params['beauty_id']))->setInc('vote_count') === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
			
			M()->commit();
			
			return $this->getInfo($vote_id);
		}
	}
	
	public function infoDelete($id){
		$result = $this->beautyVoteDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->beautyVoteDao()->where(array('vote_id'=>$info['vote_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->beautyVoteDao()->where(array('vote_id'=>$info['vote_id']))->save(array('is_open'=>$is_open));
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
		
		$beautyVoteDao = $this->beautyVoteDao();
		$count = $beautyVoteDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'vote_id desc' : $params['orderby'];
			$list = $beautyVoteDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$beautyVoteDao = $this->beautyVoteDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $beautyVoteDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = $beauty_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$beauty_ids[] = $v['beauty_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$beautys = $this->beautyInfoDao()->getRecordsField($beauty_ids);
			
			foreach ($list as $k => $v) {
				//用户
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
				//会员
				$list[$k]['beauty_picture'] = $beautys[$v['beauty_id']]['picture'];
				$list[$k]['beauty_name'] = $beautys[$v['beauty_id']]['name'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			
		}
		
		return $info;
	}
	
	//返回model
	private function beautyVoteDao(){
		return D('Common/Beauty/Vote');
	}
	
	private function beautyInfoDao(){
		return D('Common/Beauty/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品