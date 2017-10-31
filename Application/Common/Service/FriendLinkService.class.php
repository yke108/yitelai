<?php
namespace Common\Service;
use Common\Basic\Pager;
class FriendLinkService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->friendlinkCatDao()->getRecord($id);
	}
	public function catCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('cat_name', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'cat_name'=>trim($params['cat_name']),
			'is_show'=>trim($params['is_show']),
			'sort_order'=>trim($params['sort_order']),
		);	
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		$friendlinkCatDao = $this->friendlinkCatDao();
		if (!$friendlinkCatDao->validate($rules)->create($data)){
			 throw new \Exception($friendlinkCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $friendlinkCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $friendlinkCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function catDelete($id){
		$result = $this->friendlinkCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$friendlinkCatDao = $this->friendlinkCatDao();
		$count = $friendlinkCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $friendlinkCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
	public function catname(){
		$l =  $this->itemtypesDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['cat_id']] = $value['cat_name'];
		}
		return $list;
	}

	//调用model
	private function friendlinkCatDao(){
		return D('Common/Friendlink/FriendlinkCat');
	}

	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->friendlinkInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'cat_id'=>trim($params['cat_id']),
			'friendlink_image'=>trim($params['friendlink_image']),
			'friendlink_tel'=>trim($params['friendlink_tel']),
			'friendlink_name'=>$params['friendlink_name'],
			'friendlink_url'=>$params['friendlink_url'],
			'add_time'=>time(),
			'sort_order'=>$params['sort_order'],		
		);
		
		if($params['friendlink_id'] > 0){
			$data['friendlink_id'] = $params['friendlink_id'];
		}
		$friendlinkInfoDao = $this->friendlinkInfoDao();
		if (!$friendlinkInfoDao->validate($rules)->create($data)){
			 throw new \Exception($friendlinkInfoDao->getError());
		}
		if ($params['friendlink_id'] > 0){
			$result = $friendlinkInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $friendlinkInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$result = $this->friendlinkInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['cat_id'] > 0 && $map['cat_id'] = $params['cat_id'];
		$friendlinkInfoDao = $this->friendlinkInfoDao();
		$count = $friendlinkInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, friendlink_id DESC' : $params['orderby'];
			$list = $friendlinkInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	//调用model
	private function friendlinkInfoDao(){
		return D('Common/Friendlink/FriendlinkInfo');
	}
}//end HelpService!