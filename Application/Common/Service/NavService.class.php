<?php
namespace Common\Service;

class NavService{
	private $navDao;
	
	public function __construct(){
		$this->navDao = D('Common/Nav');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->navDao->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->navDao->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->navDao->create($data)){
			 throw new \Exception($this->navDao->getError());
		}
		
		M()->startTrans();
		
		if ($data['nav_id'] > 0){
			$result = $this->navDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$res = $this->navDao->add();
			if ($res === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		return true;
	}
	
	public function delete($id){
		M()->startTrans();
		$result = $this->navDao->delRecord($id);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function del($map){
		return $this->navDao->where($map)->delete();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['ref_type'])) {
			$map['ref_type'] = $params['ref_type'];
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['type'])) {
			$map['type'] = $params['type'];
		}
		
		if ($params['is_show']==1) {
			$map['is_show'] = 1;
		}
		
		
		$count = $this->navDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, nav_id DESC' : $params['orderby'];
			$list = $this->navDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$list[$key]['build_url']='';
				$url = $val['url'];
				if ($params['store_id']) {
					$url = str_replace('.html', '', $url).'/store_id/'.$params['store_id'].'.html';
					$list[$key]['build_url']=$url;
				}
				if(stripos($val['url'],'http')===false){
					$list[$key]['build_url']=U($url);
				}
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	private function UserService() {
		return D('User', 'Service');
	}
}//end HelpService!甜品