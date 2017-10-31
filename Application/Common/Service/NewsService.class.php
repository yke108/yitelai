<?php
namespace Common\Service;

class NewsService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->newsCatDao()->getRecord($id);
	}
	
	public function getCatInfo($map){
		return $this->newsCatDao()->findRecord($map);
	}
	
	public function catCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('cat_name', 'require', '名称是必须的'),
		);
		// 接收到的参数
		$data = array(
			'parent_id'=>trim($params['parent_id']),
			'cat_name'=>trim($params['cat_name']),
			'sort_order'=>trim($params['sort_order']),
			'is_show'=>trim($params['is_show']),
		);	
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		$newsCatDao = $this->newsCatDao();
		if (!$newsCatDao->validate($rules)->create($data)){
			 throw new \Exception($newsCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $newsCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function catDelete($id){
		//有文章的分类不能删除
		$map = array('cat_id'=>$id);
		$news_info = $this->newsInfoDao()->findRecord($map);
		if (!empty($news_info)) throw new \Exception('请先删除分类下的文章');
		$result = $this->newsCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$newsCatDao = $this->newsCatDao();
		$count = $newsCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $newsCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function catAllList($map, $orderby){
		return $this->newsCatDao()->searchAllRecords($map, $orderby);
	}
	
	public function catTreeList($map, $orderby){
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function catTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
	
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'cat_name'=>$vo['cat_name'],
					'sort_order'=>$vo['sort_order'],
					'edit_url'=>U('edit', array('cat_id'=>$vo[cat_id])),
					'del_url'=>U('del', array('cat_id'=>$vo[cat_id])),
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$cat_name</td>
				    <td>\$sort_order</td>
					<td>
						<a href='\$edit_url'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
	
		return $categorys;
	}
	
	public function catOptionList($params, $select_id){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
	
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<option value=\$id \$selected>\$spacer\$name</option>";
		$categorys = $tree->get_tree(0, $str, $select_id);
	
		return $categorys;
	}
	
	//获取分类的所有子类（包含自己）
	public function catChilds($cat_id){
		$use_category = false;
		$cat_list = $this->newsCatDao()->getField('cat_id, parent_id');
		$cats = array();
		foreach($cat_list as $ck => $cv){
			if($ck == $cat_id) {
				$use_category = true;
			}
			$cats[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($cat_id, $cats, $clist);
		}
		return $clist;
	}
	
	private function newsCatDao(){
		//调用model
		return D('Common/News/NewsCat');
	}

	//news_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('title', 'require', '标题不能为空'),
			array('cat_id', 'require', '分类不能为空', 1),
		);
		//参数
		$data = array(
			'cat_id'=>trim($params['cat_id']),
			'is_open'=>trim($params['is_open']),
			'add_time'=>time(),
			'update_time'=>time(),		
			'title'=>trim($params['title']),
			'picture'=>trim($params['picture']),/*---*/
			'description'=>trim($params['description']),
			'content'=>$params['content'],/*----*/
			'sort_order'=>trim($params['sort_order']),
			//'strfrom'=>trim($params['strfrom']),		
		);
		if($params['news_id'] > 0){
			$data['news_id'] = $params['news_id'];
		}
		$newsInfoDao = $this->newsInfoDao();
		if (!$newsInfoDao->validate($rules)->create($data)){
			 throw new \Exception($newsInfoDao->getError());
		}
		if ($params['news_id'] > 0){
			$result = $newsInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
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
			$clist = $this->catChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		
		$newsInfoDao = $this->newsInfoDao();
		$count = $newsInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $newsInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$newsInfoDao = $this->newsInfoDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $newsInfoDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (empty($list)) {
			return $list;
		}
		foreach ($list as $k => $v) {
			$cat_ids[] = $v['cat_id'];
		}
		$map['cat_id'] = array('in', $cat_ids);
		$cats = $this->newsCatDao()->allRecordsField($map);
		foreach ($list as $k => $v) {
			$list[$k]['cat_name'] = $cats[$v['cat_id']];
		}
		return $list;
	}
	
	private function newsInfoDao(){
		//返回model
		return D('Common/News/NewsInfo');
	}
}//end HelpService!甜品