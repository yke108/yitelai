<?php
namespace Common\Service;
class HelpService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->helpCatDao()->getRecord($id);
	}
	public function catCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('cat_name', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'cat_name'=>trim($params['cat_name']),
			'sort_order'=>trim($params['sort_order']),
		);	
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		$helpCatDao = $this->helpCatDao();
		if (!$helpCatDao->validate($rules)->create($data)){
			 throw new \Exception($helpCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $helpCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $helpCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function catDelete($id){
		$result = $this->helpCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$helpCatDao = $this->helpCatDao();
		$count = $helpCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $helpCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	// catList()方法查询cat列表
	public function catList($id){
		return  $this->helpCatDao()->allRecords();
	}
	//调用model
	private function helpCatDao(){
		return D('Common/Help/HelpCat');
	}

	// help_grp
	public function getGrp($id){
		if ($id < 1) return false;
		return $this->helpGrpDao()->getRecord($id);
	}
	//添加的方法
	public function grpCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('grp_name', 'require', '名称是必须的'),
		);
		// 接收到的参数
		$data = array(
			'grp_name'=>trim($params['grp_name']),
			'sort_order'=>trim($params['sort_order']),
			'is_show'=>trim($params['is_show']),
		);	
		if($params['grp_id'] > 0){
			$data['grp_id'] = $params['grp_id'];
		}
		$helpGrpDao = $this->helpGrpDao();
		if (!$helpGrpDao->validate($rules)->create($data)){
			 throw new \Exception($helpGrpDao->getError());
		}
		if ($params['grp_id'] > 0){
			$result = $helpGrpDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $helpGrpDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	public function grpDelete($id){
		$result = $this->helpGrpDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	public function grpPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$helpGrpDao = $this->helpGrpDao();
		$count = $helpGrpDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $helpGrpDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	// grpList()方法查询grp列表
	public function grpList($id){
		// 把查询到的信息返回给控制器
		return  $this->helpGrpDao()->allRecords();
	}
	//调用model
	private function helpGrpDao(){
		return D('Common/Help/HelpGrp');
	}

	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->helpInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'cat_id'=>trim($params['cat_id']),
			'grp_id'=>trim($params['grp_id']),
			'article_title'=>trim($params['article_title']),
			'sort_order'=>trim($params['sort_order']),
			'is_show'=>$params['is_show'],
			'add_time'=>time(),	
			'description'=>$params['description'],
			'sort_order'=>$params['sort_order'],		
		);
		
		if($params['article_id'] > 0){
			$data['article_id'] = $params['article_id'];
		}
		$helpInfoDao = $this->helpInfoDao();
		if (!$helpInfoDao->validate($rules)->create($data)){
			 throw new \Exception($helpInfoDao->getError());
		}
		if ($params['article_id'] > 0){
			$result = $helpInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $helpInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$result = $this->helpInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$helpInfoDao = $this->helpInfoDao();
		$count = $helpInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $helpInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	//调用model
	private function helpInfoDao(){
		return D('Common/Help/HelpInfo');
	}
}//end HelpService!