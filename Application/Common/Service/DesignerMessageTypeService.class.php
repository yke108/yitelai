<?php
namespace Common\Service;

class DesignerMessageTypeService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->designerMessageTypeDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		$designerMessageTypeDao = $this->designerMessageTypeDao();
		if (!$designerMessageTypeDao->create($data)){
			 throw new \Exception($designerMessageTypeDao->getError());
		}
		if ($params['type_id'] > 0){
			$result = $designerMessageTypeDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerMessageTypeDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->designerMessageTypeDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$designerMessageTypeDao = $this->designerMessageTypeDao();
		$count = $designerMessageTypeDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order DESC, type_id DESC' : $params['orderby'];
			$list = $designerMessageTypeDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function infoAllList($map){
		return $this->designerMessageTypeDao()->searchFieldRecords($map);;
	}
	
	public function isShow($type_id){
		$info = $this->getInfo($type_id);
		$is_show = $info['is_show'] == 0 ? 1 : 0;
		$result = $this->designerMessageTypeDao()->where(array('type_id'=>$info['type_id']))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function designerMessageTypeDao(){
		return D('Common/Designer/DesignerMessageType');
	}
}//end HelpService!