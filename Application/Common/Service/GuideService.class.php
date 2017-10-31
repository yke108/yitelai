<?php
namespace Common\Service;
class GuideService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->guideDao()->getRecord($id);
	}
	
	public function guideCreateOrModify($params){
		//$rules = array(
//				array('pikai_item', 'require', '批开项目是必须的'),
//				array('pikai_file_name', 'require', '需求文件名称是必须的'),
//				array('city', 'require', '城市是必须的'),
//		);
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if($params['id'] > 0){
			$data['id'] = $params['id'];
		}
		$ImportRecordDao = $this->guideDao();
		if (!$ImportRecordDao->validate($rules)->create($data)){
			 throw new \Exception($ImportRecordDao->getError());
		}
		if ($params['id'] > 0){
			$result = $ImportRecordDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败',1);
			}
		} else {
			$result = $ImportRecordDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败',1);
			}
		}
	}
	
	public function guideDelete($id,$person_id){
		$info=$this->getInfo($id);
		$powers=explode(',',$info['del_powers']);
		if(!in_array($person_id,$powers)){
			throw new \Exception('你没有删除的权限');
		}
		$result = $this->guideDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map=array();
		if(!empty($params['map'])){
			
			$map=array_merge($map,$params['map']);
		}
		
		$ImportRecordDao = $this->guideDao();
		$count = $ImportRecordDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id desc' : $params['orderby'];
			$list = $ImportRecordDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	
	
	
	
	private function guideDao(){
		return D('Common/Guide/guide');
	}
}