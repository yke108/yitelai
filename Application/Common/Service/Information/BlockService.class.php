<?php
namespace Common\Service\Information;

class BlockService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->blockDao()->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->blockDao()->findRecord($map);
	}
	
	public function blockCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$blockDao = $this->blockDao();
		if (!$blockDao->create($data)){
			throw new \Exception($blockDao->getError());
		}
		if ($params['block_id'] > 0){
			$result = $blockDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $blockDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function blockDelete($id){
		$result = $this->blockDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function blockPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		
		$blockDao = $this->blockDao();
		$count = $blockDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $blockDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function blockAllList($map, $orderby){
		return $this->blockDao()->searchAllRecords($map, $orderby);
	}
	
	public function blockTreeList($map, $orderby){
		$list = $this->blockDao()->searchAllRecords($map, $orderby);
		return genTree($list, 'block_id', 'parent_id');
	}
	
	public function blockTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, block_id DESC' : $params['orderby'];
		$list = $this->blockDao()->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['block_id']] = array(
					'id'=>$vo['block_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['name'],
					'sort_order'=>$vo['sort_order'],
					'edit_url'=>U('edit', array('block_id'=>$vo[block_id])),
					'del_url'=>U('del', array('block_id'=>$vo[block_id])),
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$name</td>
				    <td>\$sort_order</td>
					<td>
						<a href='\$edit_url' class='cs_ajax_link hy_show_modal'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function blockOptionList($params, $select_id){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, block_id DESC' : $params['orderby'];
		$list = $this->blockDao()->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['block_id']] = array(
					'id'=>$vo['block_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['name'],
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
	public function catChilds($block_id){
		$use_category = false;
		$cat_list = $this->blockDao()->getField('block_id, parent_id');
		$cats = array();
		foreach($cat_list as $ck => $cv){
			if($ck == $block_id) {
				$use_category = true;
			}
			$cats[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($block_id, $cats, $clist);
		}
		return $clist;
	}
	
	private function blockDao(){
		return D('Common/Information/Community/Block');
	}
}//end HelpService!甜品