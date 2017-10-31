<?php
namespace Common\Service;

class ZanService{
	const REF_TYPE_GOODS = 1; //商品
	const REF_TYPE_CASE = 2; //案例
	const REF_TYPE_NEWS = 3; //新闻
	const REF_TYPE_FANS = 4; //粉丝故事会
	const REF_TYPE_SERVE = 5; //家居服务
	
	private $LogDao;
	
	public function __construct(){
		$this->LogDao = D('Common/Zan/Log');
	}
	
	public function getReftypes(){
		return array(
				'1'=>'商品',
				'2'=>'案例',
				'3'=>'新闻',
				'4'=>'粉丝故事会',
				'5'=>'家居服务',
		);
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->LogDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->LogDao->create($data)){
			 throw new \Exception($this->LogDao->getError());
		}
		
		M()->startTrans();
		
		if ($data['zan_id'] > 0){
			$result = $this->LogDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$zan_id = $this->LogDao->add();
			if ($zan_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		return true;
	}
	
	public function delete($id){
		M()->startTrans();
		$result = $this->LogDao->delRecord($id);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['ref_type'])) {
			$map['ref_type'] = $params['ref_type'];
		}
		if (!empty($params['keyword'])) {
			$map['name'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$count = $this->LogDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'zan_id DESC' : $params['orderby'];
			$list = $this->LogDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	private function outputForList($list) {
		foreach ($list as $k=> $v) {
			$user_ids[] = $v['user_id'];
		}
		$users = $this->UserService()->getUsers($user_ids);
		foreach ($list as $k=> $v) {
			$list[$k]['user_name'] = $users[$v['user_id']]['nick_name'];
			
			if ($v['ref_type'] == 4) { //粉丝故事会
				$story_info = $this->storyInfoDao()->find($v['ref_id']);
				$list[$k]['name'] = $story_info['story_title'];
			}
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		return $info;
	}
	
	private function UserService() {
		return D('User', 'Service');
	}
	
	private function storyInfoDao() {
		return D('Common/Story/StoryInfo');
	}
}//end HelpService!甜品