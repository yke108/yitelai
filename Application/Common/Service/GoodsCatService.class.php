<?php
namespace Common\Service;

use Common\Basic\CsException;

class GoodsCatService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->goodsCatDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array(
				'parent_id'=>trim($params['parent_id']),
				'cat_name'=>trim($params['cat_name']),
				'picture'=>trim($params['picture']),
				'sort_order'=>trim($params['sort_order']),
				'is_show'=>trim($params['is_show']),
				//'is_floor'=>trim($params['is_floor']),
				'floor_name'=>trim($params['floor_name']),
				'floor_picture'=>trim($params['floor_picture']),
				'floor_link'=>trim($params['floor_link']),
				'is_recommend'=>trim($params['is_recommend']),
				'type_id'=>trim($params['type_id']),
				'scene'=>$params['scene'] ? implode(',', $params['scene']) : '',
				'label'=>$params['label'] ? implode(',', $params['label']) : '',
		);
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		if (!$this->goodsCatDao()->create($data)){
			 throw new \Exception($this->goodsCatDao()->getError());
		}
		
		M()->startTrans();
		
		if ($params['cat_id'] > 0){
			$result = $this->goodsCatDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			$cat_id = $params['cat_id'];
		} else {
			$cat_id = $this->goodsCatDao()->add();
			if ($cat_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//所有下级分类继承商品参数、场景和标签
		$child_ids = $this->getCatChilds($cat_id);
		$map['cat_id'] = array('in', $child_ids);
		$data = array(
				'type_id'=>trim($params['type_id']),
				//'scene'=>$params['scene'] ? implode(',', $params['scene']) : '',
				//'label'=>$params['label'] ? implode(',', $params['label']) : '',
		);
		$result = $this->goodsCatDao()->updateRecords($map, $data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($id){
		//分类下有商品不能删除
		$where = array(
				'cat_id'=>$id
		);
		$goods = M('goods')->where($where)->find();
		if ($goods) throw new \Exception('请先删除分类下的商品');
		
		$clist = $this->getCatChilds($id);
		
		$result = $this->goodsCatDao()->delRecords($clist);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	//获取分类的所有子类（包含自己）
	public function getCatChilds($cat_id){
		$use_category = false;
		$cat_list = $this->goodsCatDao()->getField('cat_id,parent_id');
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
		$list = $this->goodsCatDao()->where($map)->getField('cat_id,cat_name,parent_id,picture');
		foreach($list as $ck => $cv){
			$map=array('parent_id'=>$ck);
			$child_list = $this->goodsCatDao()->where($map)->getField('cat_id,cat_name,parent_id,picture');
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
		
		$count = $this->goodsCatDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
			$list = $this->goodsCatDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			$list = genTree($list, 'cat_id', 'parent_id');
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map){
		$count = $this->goodsCatDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = 'sort_order ASC, cat_id DESC';
			$list = $this->goodsCatDao()->searchAllRecords($map, $orderby);
			$list = genTree($list, 'cat_id', 'parent_id');
		}
		return array(
				//'list'=>$this->outputForList($list),
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function selectAllList($map) {
		$orderby = 'sort_order ASC, cat_id DESC';
		return $this->goodsCatDao()->searchAllRecords($map, $orderby);
	}
	
	public function getTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->goodsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					//'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
					'picture'=>$vo['picture'] ? '<img src="'.picurl($vo['picture']).'" height="40" />' : '',
					//'cat_brief'=>$vo['cat_brief'],
					'status'=>$vo['is_show'] ? '<font color="green">是</font>' : '<font color="red">否</font>',
					//'floor_status'=>$vo['is_floor'] ? '<font color="green">是</font>' : '<font color="red">否</font>',
					'floor_name'=>$vo['floor_name'],
					'type_name'=>$vo['type_name'],
					'sort_order'=>$vo['sort_order'],
					'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
					'edit_url'=>U('edit', array('cat_id'=>$vo[cat_id])),
					'del_url'=>U('del', array('cat_id'=>$vo[cat_id])),
					'goods_url'=>U('Goods/Index/index', array('cat_id'=>$vo[cat_id]))
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$picture</td>
					<td>\$spacer\$name</td>
					<td>\$status</td>
					<td>\$floor_name</td>
					<td>\$type_name</td>
				    <td>\$sort_order</td>
					<td>\$add_time</td>
					<td>
						<a href='\$edit_url'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
						<a href='\$goods_url'>查看分类商品</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function getDisCatTrList($params){
		$map = $params['map'];
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id DESC' : $params['orderby'];
		$list = $this->goodsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
		
		$map = array(
				'distributor_id'=>$params['distributor_id'],
		);
		$avl = $this->goodsCatDistributorDao()->where($map)->getField('cat_id, distributor_id');
	
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
					'picture'=>$vo['picture'] ? '<img src="'.picurl($vo['picture']).'" height="40" />' : '',
					'status'=>$avl[$vo['cat_id']] ? '<font color="green">是</font>' : '<font color="red">否</font>',
					'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
					'st_url'=>U('distributor/cat/st', array(
							'cat_id'=>$vo[cat_id],
							'distributor_id'=>$params['distributor_id'],
					)),
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$id</td>
					<td>\$picture</td>
					<td>\$spacer\$name</td>
					<td><a class='cs_ajax_link cs_flesh_page' href='\$st_url'>\$status</a></td>
					<td>\$add_time</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
	
		return $categorys;
	}
	
	public function setDisCat($params){
		$map = array(
				'distributor_id'=>$params['distributor_id'],
				'cat_id'=>$params['cat_id'],
		);
		$dao = $this->goodsCatDistributorDao();
		$info = $dao->findRecord($map);
		if (empty($info)){
			if ($dao->addRecord($map) < 1) throw new CsException('操作失败');
		} else { 
			if ($dao->delete($info['log_id']) === false) throw new CsException('操作失败');
		}
	}
	
	public function getDisCatList($distributor_id){
		$map = array(
				'distributor_id'=>$distributor_id,
		);
		return $this->goodsCatDistributorDao()->where($map)->getField('cat_id, cat_id as b');
	}
	
	public function getOptionList($select_id, $map = array()){
		$orderby = 'sort_order ASC, cat_id DESC';
		$list = $this->goodsCatDao()->searchAllRecords($map, $orderby);
		
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
	
	public function getDisOptionList($distributor_id, $select_id){
		$map = array(
				'distributor_id'=>$distributor_id,
		);
		$idl = $this->goodsCatDistributorDao()->where($map)->getField('cat_id, cat_id as b');
		$list = $pids = array();
		//查找本级
		if (count($idl) > 0){
			$map = array(
					'cat_id'=>array('in', $idl),
			);
			$tpl = $this->goodsCatDao()->where($map)->getField('cat_id, parent_id, cat_name');
			foreach ($tpl as $vo){
				$list[$vo['cat_id']] = array(
						'id'=>$vo['cat_id'],
						'parentid'=>$vo['parent_id'],
						'name'=>$vo['cat_name'],
				);
				$vo['parent_id'] > 0 && $pids[] = $vo['parent_id'];
			}
		}
		//查找上级
		if (count($pids) > 0){
			$map = array(
					'cat_id'=>array('in', $pids),
			);
			$tpl = $this->goodsCatDao()->where($map)->getField('cat_id, parent_id, cat_name');
			$pids = array();
			foreach ($tpl as $vo){
				$list[$vo['cat_id']] = array(
						'id'=>$vo['cat_id'],
						'parentid'=>$vo['parent_id'],
						'name'=>$vo['cat_name'],
				);
				$vo['parent_id'] > 0 && $pids[] = $vo['parent_id'];
			}
		}
		//查找上级
		if (count($pids) > 0){
			$map = array(
					'cat_id'=>array('in', $pids),
			);
			$tpl = $this->goodsCatDao()->where($map)->getField('cat_id, parent_id, cat_name');
			$pids = array();
			foreach ($tpl as $vo){
				$list[$vo['cat_id']] = array(
						'id'=>$vo['cat_id'],
						'parentid'=>$vo['parent_id'],
						'name'=>$vo['cat_name'],
				);
			}
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($list);
		$str = "<option value=\$id \$selected>\$spacer\$name</option>";
		$categorys = $tree->get_tree(0, $str, $select_id);
		return $categorys;
	}
	
	function get_top_parentid($cat_id){
		$r = $this->goodsCatDao()->where('cat_id = '.$cat_id)->field('cat_id, parent_id')->find();
		if($r['parent_id'] != '0') return $this->get_top_parentid($r['parent_id']);
		return $r['cat_id'];
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$type_list = $this->goodsTypeDao()->searchAllRecords();
			$brand_list = $this->goodsBrandDao()->searchAllRecords();
			foreach ($brand_list as $v) {
				$new_brand_list[$v['cat_id']][] = $v;
			}
			foreach ($list as $k => $v) {
				//品牌
				$list[$k]['brand_list'] = $new_brand_list[$v['cat_id']];
					
				//类型
				$list[$k]['type_name'] = $type_list[$v['type_id']];
					
				//获取场景和标签
				if ($v['scene']) {
					$map = array(
							'scene_id'=>array('in', $v['scene'])
					);
					$scene_list = $this->goodsSceneDao()->selectAllRecords($map);
					$list[$k]['scene_list'] = $scene_list;
				}
					
				if ($v['label']) {
					$map = array(
							'label_id'=>array('in', $v['label'])
					);
					$label_list = $this->goodsLabelDao()->searchAllRecords($map);
					$list[$k]['label_list'] = $label_list;
				}
			}
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			//品牌
			$map = array('cat_id'=>$info['cat_id']);
			$orderBy = 'sort_order ASC, brand_id DESC';
			$info['brand_list'] = $this->goodsBrandDao()->searchAllRecords($map, $orderBy);
			
			//获取场景和标签
			$info['scene_ids'] = explode(',', $info['scene']);
			$info['label_ids'] = explode(',', $info['label']);
			if ($info['scene']) {
				$map = array(
						'scene_id'=>array('in', $info['scene'])
				);
				$scene_list = $this->goodsSceneDao()->searchAllRecords($map);
				$info['scene_list'] = $scene_list;
			}
			
			if ($info['label']) {
				$map = array(
						'label_id'=>array('in', $info['label'])
				);
				$label_list = $this->goodsLabelDao()->searchAllRecords($map);
				$info['label_list'] = $label_list;
			}
			
			//筛选条件
			$spec_list = $this->goodsSpecDao()->selectAllRecords();
			$spec_ids = $filter_list = array();
			foreach ($spec_list as $v) {
				if ($v['type_id'] == $info['type_id']) {
					$spec_ids[] = $v['spec_id'];
					$filter_list[] = $v;
				}
			}
			$spec_values = M('goods_spec_values')->distinct(true)->field('goods_spec_value_id, spec_id, spec_value')->select();
			foreach ($filter_list as $k => $v) {
				$values = array();
				foreach ($spec_values as $v2) {
					if ($v2['spec_id'] == $v['spec_id']) {
						$values[] = $v2;
					}
				}
				$filter_list[$k]['spec_values'] = $values;
			}
			$info['filter_list'] = $filter_list;
		}
		return $info;
	}
	
	private function goodsCatDao() {
		return D('Common/Goods/GoodsCat');
	}
	
	private function goodsBrandDao() {
		return D('Common/Goods/GoodsBrand');
	}
	
	private function goodsCatDistributorDao() {
		return D('Common/Goods/GoodsCatDistributor');
	}
	
	private function goodsTypeDao() {
		return D('Common/Goods/GoodsType');
	}
	
	private function goodsSpecDao() {
		return D('Common/Goods/GoodsSpec');
	}
	
	private function goodsSceneDao() {
		return D('Common/Goods/GoodsScene');
	}
	
	private function goodsLabelDao() {
		return D('Common/Goods/GoodsLabel');
	}
}//end HelpService!甜品