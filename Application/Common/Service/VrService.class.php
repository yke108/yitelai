<?php
namespace Common\Service;
class VrService{
	//vr_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->vrInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('vr_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'vr_title'=>trim($params['vr_title']),
			'vr_image'=>trim($params['vr_image']),
			'vr_url'=>$params['vr_url'],
			'add_time'=>time(),
			'sort_order'=>$params['sort_order'],		
		);
		
		if($params['vr_id'] > 0){
			$data['vr_id'] = $params['vr_id'];
		}
		$vrInfoDao = $this->vrInfoDao();
		if (!$vrInfoDao->validate($rules)->create($data)){
			 throw new \Exception($vrInfoDao->getError());
		}
		if ($params['vr_id'] > 0){
			$result = $vrInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $vrInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$result = $this->vrInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		//搜索
		!empty($params['vr_title']) && $map['vr_title'] = array('like','%'.$params['vr_title'].'%');
		$vrInfoDao = $this->vrInfoDao();
		$count = $vrInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $vrInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	// catList()方法查询cat列表
	public function catList($params){
		empty($params['orderby']) && $orderBy = 'sort_order asc';
		return  $this->friendlinkCatDao()->allRecords();
	}
	//键值数组
	public function catname(){
		$l =  $this->itemtypesDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['cat_id']] = $value['cat_name'];
		}
		return $list;
	}
	//调用model
	private function vrInfoDao(){
		return D('Common/Vr/VrInfo');
	}
}//end HelpService!