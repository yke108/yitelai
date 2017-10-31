<?php
namespace Common\Service;
class ServeService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->serveCatDao()->getRecord($id);
	}
	public function catCreateOrModify($params){
		// 自动验证//
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
		$serveCatDao = $this->serveCatDao();
		if (!$serveCatDao->validate($rules)->create($data)){
			 throw new \Exception($serveCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $serveCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $serveCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function catDelete($id){
		$result = $this->serveCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		//搜索//
		!empty($params['cat_name']) && $map['cat_name'] = array('like','%'.$params['cat_name'].'%');
		$serveCatDao = $this->serveCatDao();
		$count = $serveCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $serveCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		return  $this->serveCatDao()->allRecords();
	}
	public function catname(){
		$l =  $this->serveCatDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['cat_id']] = $value['cat_name'];
		}
		return $list;
	}

	//调用model
	private function serveCatDao(){
		return D('Common/Serve/ServeCat');
	}

	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->serveInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
				array('serve_name', 'require', '名称不能为空'),
				array('serve_tel', 'require', '电话不能为空'),
				array('serve_url', 'require', '链接不能为空'),
				array('serve_content', 'require', '简介不能为空'),
		);
		//参数
		$data = array(
			'serve_name'=>trim($params['serve_name']),
			'cat_id'=>trim($params['cat_id']),
			'serve_image'=>trim($params['serve_image']),
			'serve_tel'=>trim($params['serve_tel']),
			'serve_url'=>$params['serve_url'],
			'add_time'=>time(),
			'serve_content'=>trim($params['serve_content']),
			'sort_order'=>$params['sort_order'],
		);
		
		if($params['serve_id'] > 0){
			$data['serve_id'] = $params['serve_id'];
		}
		$serveInfoDao = $this->serveInfoDao();
		if (!$serveInfoDao->validate($rules)->create($data)){
			 throw new \Exception($serveInfoDao->getError());
		}
		if ($params['serve_id'] > 0){
			$result = $serveInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $serveInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$result = $this->serveInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询  
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		//搜索
		!empty($params['serve_name']) && $map['serve_name'] = array('like','%'.$params['serve_name'].'%');
		$params['cat_id'] > 0 && $map['cat_id'] = $params['cat_id'];
		$serveInfoDao = $this->serveInfoDao();
		$count = $serveInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $serveInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function infoLike($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
	
		if (!in_array($params['type'], array(1,2))) {
			throw new \Exception('类型不正确');
		}
	
		$story_info=$this->getInfo($params['serve_id']);
		if(empty($story_info)){
			throw new \Exception('文章不存在');
		}
	
		if($story_info['user_id']==$params['user_id']){
			throw new \Exception('抱歉，您不能给自己的文章点赞。');
		}
	
		$map = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['serve_id'],
				'ref_value'=>$params['type']
		);
		$zan = $this->zanDao()->findRecord($map);
		if ($zan) {
			throw new \Exception('你已经点赞过');
		}
	
		M()->startTrans();
	
		$result = $this->serveInfoDao()->like($params['serve_id']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('点赞失败');
		}
	
		$data = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['serve_id'],
				'name'=>$story_info['serve_name'],
				'ref_value'=>$params['type']
		);
		if (!$this->zanDao()->create($data)){
			throw new \Exception($this->zanDao()->getError());
		}
		$result = $this->zanDao()->add();
		if ($result === false) {
			M()->rollback();
			throw new \Exception('点赞失败');
		}
	
		M()->commit();
	
		$info = $this->serveInfoDao()->getRecord($params['serve_id']);
		return $info['good_num'];
	}
	
	public function infoClap($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
	
		if (!in_array($params['type'], array(1,2))) {
			throw new \Exception('类型不正确');
		}
	
		$story_info=$this->getInfo($params['serve_id']);
		if(empty($story_info)){
			throw new \Exception('文章不存在');
		}
	
		if($story_info['user_id']==$params['user_id']){
			throw new \Exception('抱歉，您不能给自己的文章拍砖。');
		}
	
		$map = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['serve_id'],
				'ref_value'=>$params['type']
		);
		$zan = $this->zanDao()->findRecord($map);
		if ($zan) {
			throw new \Exception('你已经拍砖过');
		}
	
		M()->startTrans();
	
		$result = $this->serveInfoDao()->clap($params['serve_id']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('拍砖失败');
		}
	
		$data = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['serve_id'],
				'name'=>$story_info['serve_name'],
				'ref_value'=>$params['type']
		);
		if (!$this->zanDao()->create($data)){
			throw new \Exception($this->zanDao()->getError());
		}
		$result = $this->zanDao()->add();
		if ($result === false) {
			M()->rollback();
			throw new \Exception('拍砖失败');
		}
	
		M()->commit();
	
		$info = $this->serveInfoDao()->getRecord($params['serve_id']);
		return $info['bad_num'];
	}
	
	//调用model
	private function serveInfoDao(){
		return D('Common/Serve/ServeInfo');
	}
	
	private function zanDao(){
		return D('Common/Zan/Log');
	}
}//end HelpService!