<?php
namespace Common\Service;
use Common\Basic\CsException;

class MaterialLabelService{
	protected $materialLabelDao;
	
	public function __construct(){
		$this->materialLabelDao = D('Common/Material/Label');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->materialLabelDao->getRecord($id);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->materialLabelDao->create($data)){
			 throw new \Exception($this->materialLabelDao->getError());
		}
		if ($params['label_id'] > 0){
			$result = $this->materialLabelDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
			$label_id = $params['label_id'];
		} else {
			$label_id = $this->materialLabelDao->add();
			if ($label_id < 1){
				throw new \Exception('添加失败');
			}
		}
		
		return true;
	}
	
	public function delete($id){
		$result = $this->materialLabelDao->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	//获取分类的所有子类（包含自己）
	public function getCatChilds($label_id){
		$use_category = false;
		$cat_list = $this->materialLabelDao->getField('label_id,parent_id');
		$cats = array();
		foreach($cat_list as $ck => $cv){
			if($ck == $label_id) {
				$use_category = true;
			}
			$cats[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($label_id, $cats, $clist);
		}
		
		return $clist;
	}
	
	//获取分类的所有子类（包含自己）
	public function getChildsData($label_id){
		$use_category = false;
		if(empty($label_id)){return;}
		$map=array('parent_id'=>$label_id);
		$list = $this->materialLabelDao->where($map)->getField('label_id,label_name,parent_id,picture');
		foreach($list as $ck => $cv){
			$map=array('parent_id'=>$ck);
			$child_list = $this->materialLabelDao->where($map)->getField('label_id,label_name,parent_id,picture');
			$list[$ck]['child']=$child_list;
		}
		
		return $list;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		if ($params['map']) {
			$map = $params['map'];
		}else {
			$map = array();
		}
		if ($params['is_floor']) {
			$map['is_floor'] = $params['is_floor'];
		}
		if ($params['is_show']) {
			$map['is_show'] = $params['is_show'];
		}
		
		$count = $this->materialLabelDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, label_id DESC' : $params['orderby'];
			$list = $this->materialLabelDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			$list = genTree($list, 'label_id', 'parent_id');
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map){
		$count = $this->materialLabelDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = 'sort_order ASC, label_id DESC';
			$list = $this->materialLabelDao->searchAllRecords($map, $orderby);
			$list = genTree($list, 'label_id', 'parent_id');
		}
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function selectAllList($map) {
		$orderby = 'sort_order ASC, label_id DESC';
		return $this->materialLabelDao->searchAllRecords($map, $orderby);
	}
	
	public function getTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, label_id DESC' : $params['orderby'];
		$list = $this->materialLabelDao->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$url = U('recommend',array('label_id'=>$vo['label_id']));
			if ($vo['is_recommend']) {
				$is_recommend = '<a class="cs_ajax_link label label-success cs_flesh_page" href="'.$url.'">是</a>';
			}else {
				$is_recommend = '<a class="cs_ajax_link label label-danger cs_flesh_page" href="'.$url.'">否</a>';
			}
			$result[$vo['label_id']] = array(
					'id'=>$vo['label_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['label_name'],
					'status'=>$is_recommend,
					'sort_order'=>$vo['sort_order'],
					'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
					'edit_url'=>U('edit', array('label_id'=>$vo[label_id])),
					'del_url'=>U('del', array('label_id'=>$vo[label_id])),
					'view_url'=>U('Material/Index/index', array('label_id'=>$vo[label_id]))
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$name</td>
				    <td>\$sort_order</td>
					<td>\$status</td>
					<td>\$add_time</td>
					<td>
						<a href='\$edit_url' class='cs_ajax_link hy_show_modal'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function getOptionList($select_id, $map = array()){
		$orderby = 'sort_order ASC, label_id DESC';
		$list = $this->materialLabelDao->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['label_id']] = array(
					'id'=>$vo['label_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['label_name'],
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
	
	public function recommend($label_id){
		$info = $this->getInfo($label_id);
		if(empty($info)) throw new \Exception('推荐失败');
		$data = array('label_id'=>$label_id, 'is_recommend'=>($info['is_recommend'] == 1 ? 0 : 1));
		$result = $this->materialLabelDao->save($data);
		if($result === false) throw new \Exception('推荐失败');
	}
}//end HelpService!甜品