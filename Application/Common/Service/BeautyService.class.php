<?php
namespace Common\Service;

class BeautyService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->beautyInfoDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['start_time'] = strtotime($params['start_time']);
		$data['end_time'] = strtotime($params['end_time']);
		
		$beautyInfoDao = $this->beautyInfoDao();
		if (!$beautyInfoDao->create($data)){
			 throw new \Exception($beautyInfoDao->getError());
		}
		
		if ($params['beauty_id'] > 0){
			$result = $beautyInfoDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $beautyInfoDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->beautyInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($beauty_id){
		$info = $this->getInfo($beauty_id);
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->beautyInfoDao()->where(array('beauty_id'=>$info['beauty_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($beauty_id){
		$info = $this->getInfo($beauty_id);
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->beautyInfoDao()->where(array('beauty_id'=>$info['beauty_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function readCount($beauty_id){
		$this->beautyInfoDao()->where(array('beauty_id'=>$beauty_id))->setInc('read_count');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		
		$beautyInfoDao = $this->beautyInfoDao();
		$count = $beautyInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc, beauty_id desc' : $params['orderby'];
			$list = $beautyInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$beautyInfoDao = $this->beautyInfoDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $beautyInfoDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$cats = $this->beautyCatDao()->allRecordsField();
			
			foreach ($list as $k => $v) {
				$list[$k]['cat_name'] = $cats[$v['cat_id']];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			$map = array('vote_count'=>array('egt', $info['vote_count']));
			$info['paiming'] = $this->beautyInfoDao()->where($map)->count();
		}
		
		return $info;
	}
	
	//返回model
	private function beautyInfoDao(){
		return D('Common/Beauty/Info');
	}
	
	private function beautyCatDao(){
		return D('Common/Beauty/Cat');
	}
}//end HelpService!甜品