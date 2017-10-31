<?php
namespace Common\Service\Distributor;

class DistributionService{
	protected $distributorDistribution;
	
	public function __construct(){
		$this->distributorDistribution = D('Common/Distributor/Distribution');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->distributorDistribution->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->distributorDistribution->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		if ($data['distribution_info']) {
			$distribution_info = array();
			foreach ($data['distribution_info'] as $k => $v) {
				if ( (intval($v) < 1) || (intval($v) > 100) ) {
					throw new \Exception('折扣只能输入0-100的整数');
				}
				$distribution_info[] = array('rank_id'=>$k, 'discount'=>$v);
			}
			$data['distribution_info'] = serialize($distribution_info);
		}
		
		if (!$this->distributorDistribution->create($data)){
			 throw new \Exception($this->distributorDistribution->getError());
		}
		if ($params['distribution_id'] > 0){
			$result = $this->distributorDistribution->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->distributorDistribution->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->distributorDistribution->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function del($map){
		$result = $this->distributorDistribution->where($map)->delete();
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if ($params['distributor_id']) {
			$map['distributor_id'] = array('in', array(0, $params['distributor_id']));
		}else {
			$map['distributor_id'] = 0;
		}
		
		$count = $this->distributorDistribution->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'distribution_id ASC' : $params['orderby'];
			$list = $this->distributorDistribution->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		$list = $this->distributorDistribution->searchAllRecords($map);
		return $this->outputForList($list);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$rank_list = $this->distributorRankDao()->getFieldRecord();
			
			foreach ($list as $k => $v) {
				$list[$k]['distribution_info'] = unserialize($v['distribution_info']);
				
				$option = array();
				$distribution_info = unserialize($v['distribution_info']);
				foreach ($distribution_info as $v2) {
					$option[] = $rank_list[$v2['rank_id']]['rank_name'].':'.$v2['discount'].'%';
				}
				$list[$k]['option'] = implode(' | ', $option);
			}
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			$distribution_info = array();
			$arr = unserialize($info['distribution_info']);
			foreach ($arr as $k => $v) {
				$distribution_info[$v['rank_id']] = $v;
			}
			$info['distribution_info'] = $distribution_info;
		}
		return $info;
	}
	
	private function distributorRankDao(){
		return D('Common/Distributor/DistributorRank');
	}
}//end HelpService!甜品