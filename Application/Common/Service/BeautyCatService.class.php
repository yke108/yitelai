<?php
namespace Common\Service;

class BeautyCatService{
	public function getCat($id){
		if ($id < 1) return false;
		return $this->beautyCatDao()->getRecord($id);
	}
	
	public function getCatInfo($map){
		return $this->beautyCatDao()->findRecord($map);
	}
	
	public function catCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$beautyCatDao = $this->beautyCatDao();
		if (!$beautyCatDao->create($data)){
			throw new \Exception($beautyCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $beautyCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $beautyCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function catDelete($id){
		//有会员的分类不能删除
		$map = array('cat_id'=>$id);
		$beauty_info = $this->beautyInfoDao()->findRecord($map);
		if (!empty($beauty_info)) throw new \Exception('请先删除分类下的会员');
		$result = $this->beautyCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		
		$beautyCatDao = $this->beautyCatDao();
		$count = $beautyCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $beautyCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function catAllList($map, $orderby){
		return $this->beautyCatDao()->searchAllRecords($map, $orderby);
	}
	
	public function catTreeList($map, $orderby){
		$list = $this->beautyCatDao()->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function catTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->beautyCatDao()->searchAllRecords($map, $orderby);
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
						<a href='\$edit_url' class='cs_ajax_link hy_show_modal'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
	
		return $categorys;
	}
	
	public function catOptionList($params, $select_id){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->beautyCatDao()->searchAllRecords($map, $orderby);
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
		$cat_list = $this->beautyCatDao()->getField('cat_id, parent_id');
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
	
	private function outputForList($list){
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$cat_ids[] = $v['cat_id'];
			}
			$map['cat_id'] = array('in', $cat_ids);
			$cats = $this->beautyCatDao()->allRecordsField($map);
			foreach ($list as $k => $v) {
				$list[$k]['cat_name'] = $cats[$v['cat_id']];
			}
		}
		
		return $list;
	}
	
	//返回model
	private function beautyCatDao(){
		return D('Common/Beauty/Cat');
	}
	
	private function beautyInfoDao(){
		return D('Common/Beauty/Info');
	}
}//end HelpService!甜品