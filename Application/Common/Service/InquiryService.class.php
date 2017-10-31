<?php
namespace Common\Service;

class InquiryService{
	private $inquiryDao;
	
	public function __construct(){
		$this->inquiryDao = D('Common/Inquiry');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->inquiryDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->inquiryDao->create($data)){
			 throw new \Exception($this->inquiryDao->getError());
		}
		
		M()->startTrans();
		
		if ($data['inquiry_id'] > 0){
			$result = $this->inquiryDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$res = $this->inquiryDao->add();
			if ($res === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return $this->getCount();
	}
	
	public function delete($id){
		M()->startTrans();
		$result = $this->inquiryDao->delRecord($id);
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
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (!empty($params['keyword'])) {
			$map['user_name|mobile'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$count = $this->inquiryDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'inquiry_id DESC' : $params['orderby'];
			$list = $this->inquiryDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getCount($params){
		$map = array();
		if (!empty($params['get']['ref_type'])) {
			$map['ref_type'] = $params['get']['ref_type'];
		}
		if (!empty($params['get']['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['get']['start_time']));
		}
		if (!empty($params['get']['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['get']['end_time']) + 86400);
		}
	
		return $this->inquiryDao->searchRecordsCount($map);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		foreach ($list as $k => $v) {
			$list[$k]['region'] = $this->regionDao()->getRegionName($v['region_code']);
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		if (empty($info)) {
			return $info;
		}
		$info['region'] = $this->regionDao()->getRegionName($info['region_code']);
		return $info;
	}
	
	private function regionDao() {
		return D('Common/Region');
	}
}//end HelpService!甜品