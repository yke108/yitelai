<?php
namespace Common\Service;
use Common\Basic\CsException;

class MaterialCatService{
	protected $materialCatDao;
	
	public function __construct(){
		$this->materialCatDao = D('Common/Material/Cat');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->materialCatDao->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->materialCatDao->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->materialCatDao->create($data)){
			 throw new \Exception($this->materialCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $this->materialCatDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
			$cat_id = $params['cat_id'];
		} else {
			$cat_id = $this->materialCatDao->add();
			if ($cat_id < 1){
				throw new \Exception('添加失败');
			}
		}
		
		return true;
	}
	
	public function delete($id){
		//分类下有素材不能删除
		$map = array('cat_id'=>$id);
		$info = $this->materialInfoDao()->where($map)->find();
		if ($info) throw new \Exception('请先删除分类下的素材');
		
		$clist = $this->getCatChilds($id);
		
		$result = $this->materialCatDao->delRecords($clist);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	//获取分类的所有子类（包含自己）
	public function getCatChilds($cat_id){
		$use_category = false;
		$cat_list = $this->materialCatDao->getField('cat_id,parent_id');
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
	
	//获取分类的所有子类（包含自己）
	public function getChildsData($cat_id){
		$use_category = false;
		if(empty($cat_id)){return;}
		$map=array('parent_id'=>$cat_id);
		$list = $this->materialCatDao->where($map)->getField('cat_id,cat_name,parent_id,picture');
		foreach($list as $ck => $cv){
			$map = array('parent_id'=>$ck);
			$child_list = $this->materialCatDao->where($map)->getField('cat_id,cat_name,parent_id,picture');
			$list[$ck]['child']=$child_list;
		}
		
		return $list;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['is_show']) {
			$map['is_show'] = $params['is_show'];
		}
		if ($params['is_recommend']) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		if (isset($params['parent_id'])) {
			$map['parent_id'] = $params['parent_id'];
		}
		
		$count = $this->materialCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
			$list = $this->materialCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			$list = genTree($list, 'cat_id', 'parent_id');
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function getAllList($map){
		$count = $this->materialCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = 'sort_order ASC, cat_id DESC';
			$list = $this->materialCatDao->searchAllRecords($map, $orderby);
		}
		return array(
				'list'=>genTree($list, 'cat_id', 'parent_id'),
				'count'=>$count,
		);
	}
	
	public function selectAllList($map) {
		$orderby = 'sort_order ASC, cat_id DESC';
		return $this->materialCatDao->searchAllRecords($map, $orderby);
	}
	
	public function getTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->materialCatDao->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
		
		$result = array();
		foreach ($list as $vo) {
			$url = U('recommend',array('cat_id'=>$vo['cat_id']));
			if ($vo['is_recommend']) {
				$is_recommend = '<a class="cs_ajax_link label label-success cs_flesh_page" href="'.$url.'">是</a>';
			}else {
				$is_recommend = '<a class="cs_ajax_link label label-danger cs_flesh_page" href="'.$url.'">否</a>';
			}
			$url = U('material/index/index',array('cat_id'=>$vo['cat_id']));
			$clist = $this->getCatChilds($vo['cat_id']);
			$map = array('cat_id'=>array('in', $clist));
			$material_count = $this->materialInfoDao()->searchRecordsCount($map);
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
					'material_count'=>'<a href="'.$url.'">'.$material_count.'</a>',
					'status'=>$is_recommend,
					'type_name'=>$vo['type_name'],
					'sort_order'=>$vo['sort_order'],
					'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
					'edit_url'=>U('edit', array('cat_id'=>$vo[cat_id])),
					'del_url'=>U('del', array('cat_id'=>$vo[cat_id])),
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$name</td>
					<td>\$material_count</td>
					<td>\$status</td>
					<td>\$type_name</td>
				    <td>\$sort_order</td>
					<td>\$add_time</td>
					<td>
						<a href='\$edit_url' class='cs_ajax_link hy_show_modal'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function getOptionList($select_id = 0, $map = array()){
		$orderby = 'sort_order ASC, cat_id DESC';
		$list = $this->materialCatDao->searchAllRecords($map, $orderby);
		
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
	
	public function recommend($cat_id){
		$info = $this->getInfo($cat_id);
		if(empty($info)) throw new \Exception('推荐失败');
		$data = array('cat_id'=>$cat_id, 'is_recommend'=>($info['is_recommend'] == 1 ? 0 : 1));
		$result = $this->materialCatDao->save($data);
		if($result === false) throw new \Exception('推荐失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$type_list = $this->materialTypeDao()->searchAllRecords();
			
			foreach ($list as $k => $v) {
				//类型
				$list[$k]['type_name'] = $type_list[$v['type_id']];
			}
		}
		
		return $list;
	}
	
	private function materialInfoDao() {
		return D('Common/Material/Info');
	}
	
	private function materialTypeDao() {
		return D('Common/Material/Type');
	}
}//end HelpService!甜品