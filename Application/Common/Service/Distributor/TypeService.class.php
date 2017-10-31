<?php
namespace Common\Service\Distributor;

class TypeService{
	public function __construct(){
		
	}
	
	public function getTrList($map, $orderby){
		$list = $this->distributorTypeDao()->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['type_id']] = array(
					'id'=>$vo['type_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['type_name'],
					'deposit'=>$vo['deposit'],
					'service_charge'=>$vo['service_charge'],
					'platform_take'=>$vo['platform_take'],
					'edit_url'=>U('edit', array('type_id'=>$vo[type_id])),
					'del_url'=>U('del', array('type_id'=>$vo[type_id])),
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$name</td>
        			<td>￥\$deposit</td>
					<td>￥\$service_charge</td>
					<td>\$platform_take%</td>
					<td>
						<a href='\$edit_url'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function getOptionList($map, $select_id = 0){
		$list = $this->distributorTypeDao()->searchAllRecords($map);
	
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['type_name'],
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<option value=\$id \$selected>\$spacer\$name</option>";
		$options = $tree->get_tree(0, $str, $select_id);
		
		return $options;
	}
	
	public function getInfo($id){
		return $this->distributorTypeDao()->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->distributorTypeDao()->findRecord($map);
	}
	
	public function createOrModify($params){
		//只能添加二级
		if ($params['parent_id'] > 0) {
			$map = array('type_id'=>$params['parent_id']);
			$type_info = $this->findInfo($map);
			if ($type_info['parent_id'] > 0) throw new \Exception('只能添加二级');
		}
		
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->distributorTypeDao()->create($data)) throw new \Exception($this->distributorTypeDao()->getError());
		if ($data['type_id'] > 0){
			$result = $this->distributorTypeDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->distributorTypeDao()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($type_id){
		//有下级不能删除
		$map = array(
				'parent_id'=>$type_id,
		);
		$info = $this->distributorTypeDao()->findRecord($map);
		if ($info) throw new \Exception('请先删除下级类型');
		
		$result = $this->distributorTypeDao()->delRecord($type_id);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	//获取的所有子类（包含自己）
	public function getCatChilds($type_id){
		$use_category = false;
		$type_list = $this->distributorTypeDao()->searchRecordsField();
		$types = array();
		foreach($type_list as $ck => $cv){
			if($ck == $type_id) {
				$use_category = true;
			}
			$types[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($type_id, $types, $clist);
		}
		return $clist;
	}
	
	public function getAllList($map){
		return $this->distributorTypeDao()->searchAllRecords($map, $orderby = 'type_id DESC');
	}
	
	public function getTreeList($map){
		$list = $this->distributorTypeDao()->searchAllRecords($map, $orderby = 'type_id DESC');
		return genTree($list, 'type_id', 'parent_id');
	}
	
	public function getTopList($map){
		$map['parent_id'] = 0;
		return $this->distributorTypeDao()->searchAllRecords($map, $orderby = 'type_id DESC');
	}
	
	public function getFieldList($map){
		$list = $this->distributorTypeDao()->searchRecordsField($map);
		return genTreeField($list, 'type_id', 'parent_id');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			
		}
		
		return $info;
	}
	
	private function distributorTypeDao() {
		return D('Common/Distributor/Type');
	}
}//end HelpService!甜品