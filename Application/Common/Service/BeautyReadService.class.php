<?php
namespace Common\Service;

class BeautyReadService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->beautyReadDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreate($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		M()->startTrans();
		
		if ($params['user_id']) {
			$beautyReadDao = $this->beautyReadDao();
			if (!$beautyReadDao->create($data)){
				throw new \Exception($beautyReadDao->getError());
			}
			
			$read_id = $beautyReadDao->add();
			if ($read_id < 1){
				M()->rollback();
			
			}
		}
			
		//统计浏览数
		if ($this->beautyInfoDao()->where(array('beauty_id'=>$params['beauty_id']))->setInc('read_count') === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
			
		M()->commit();
	}
	
	public function infoDelete($id){
		$result = $this->beautyReadDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->beautyReadDao()->where(array('read_id'=>$info['read_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->beautyReadDao()->where(array('read_id'=>$info['read_id']))->save(array('is_open'=>$is_open));
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
		
		$beautyReadDao = $this->beautyReadDao();
		$count = $beautyReadDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'read_id desc' : $params['orderby'];
			$list = $beautyReadDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$beautyReadDao = $this->beautyReadDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $beautyReadDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
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
	private function beautyReadDao(){
		return D('Common/Beauty/Read');
	}
	
	private function beautyInfoDao(){
		return D('Common/Beauty/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品