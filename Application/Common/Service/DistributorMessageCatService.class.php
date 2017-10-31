<?php
namespace Common\Service;

class DistributorMessageCatService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->messageCatDao()->getRecord($id);
	}
	
	public function getCatInfo($map){
		return $this->messageCatDao()->findRecord($map);
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
		$messageCatDao = $this->messageCatDao();
		if (!$messageCatDao->validate($rules)->create($data)){
			 throw new \Exception($messageCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $messageCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $messageCatDao->addRecord($data);
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
		$result = $this->messageCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$messageCatDao = $this->messageCatDao();
		$count = $messageCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $messageCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function catAllList($map, $orderby){
		return $this->messageCatDao()->searchAllRecords($map, $orderby);
	}
	
	public function catTreeList($map, $orderby){
		$list = $this->messageCatDao()->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function catTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->messageCatDao()->searchAllRecords($map, $orderby);
		
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
	
	public function catOptionList($map, $orderby = 'sort_order ASC, cat_id DESC', $select_id){
		$list = $this->messageCatDao()->searchAllRecords($map, $orderby);
		
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
		$cat_list = $this->messageCatDao()->getField('cat_id, parent_id');
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
	
	private function messageCatDao(){
		return D('Common/Distributor/MessageCat');
	}
}//end HelpService!甜品