<?php
namespace Common\Service\Distributor;

class GoodsCatService{
	protected $DistributorGoodsCatDao;
	
	public function __construct(){
		$this->DistributorGoodsCatDao = D('Common/Distributor/GoodsCat');
	}
	
	public function getTrList($map, $orderby){
		$list = $this->DistributorGoodsCatDao->searchAllRecords($map, $orderby);
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
					//'cat_logo'=>$vo['cat_logo'] ? '<img src="'.picurl($cat_logo).'" height="40" />' : '',
					'cat_brief'=>$vo['cat_brief'],
					'show_status'=>$vo['is_show'] ? '是' : '否',
					'floor_status'=>$vo['is_floor'] ? '是' : '否',
					'sort_order'=>$vo['sort_order'],
					//'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
					'edit_url'=>U('edit', array('cat_id'=>$vo[cat_id])),
					'del_url'=>U('del', array('cat_id'=>$vo[cat_id])),
					'goods_url'=>U('Goods/Index/index', array('self_cat_id'=>$vo[cat_id]))
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$name</td>
        			<td>\$cat_brief</td>
					<td>\$show_status</td>
					<td>\$floor_status</td>
				    <td>\$sort_order</td>
					<td>
						<a href='\$edit_url'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
						<a href='\$goods_url'>查看分组商品</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
	
		return $categorys;
	}
	
	public function getOptionList($map, $select_id = 0){
		$list = $this->DistributorGoodsCatDao->searchAllRecords($map);
	
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
	
	public function getPlatformList(){
		$list = $this->GoodsCatDao()->searchAllRecords();
		
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
					'picture'=>$vo['picture'] ? '<img src="'.picurl($vo['picture']).'" height="40" />' : '',
					'cat_brief'=>$vo['cat_brief'],
					'status'=>$vo['is_show'] ? '是' : '否',
					'sort_order'=>$vo['sort_order'],
					'add_time'=>date('Y-m-d H:i:s', $vo['add_time']),
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
        			<td>\$cat_brief</td>
					<td>\$status</td>
				    <td>\$sort_order</td>
					<td>\$add_time</td>
					<td>
						<a href='\$goods_url'>查看分组商品</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
	
		return $categorys;
	}
	
	public function getInfo($map){
		return $this->DistributorGoodsCatDao->findRecord($map);
	}
	
	public function getCat($id){
		return $this->DistributorGoodsCatDao->getRecord($id);
	}
	
	public function createOrModify($params){
		//有下级的不能修改上级分组
		if ($params['cat_id'] > 0 && $params['parent_id'] > 0) {
			$map = array(
					'parent_id'=>$params['cat_id'],
					'distributor_id'=>$params['distributor_id']
			);
			$child_cat = $this->DistributorGoodsCatDao->findRecord($map);
			if ($child_cat) {
				throw new \Exception('只能添加二级分组');
			}
		}
		
		// 接收到的参数
		$data = array(
				'distributor_id'=>$params['distributor_id'],
				'parent_id'=>trim($params['parent_id']),
				'cat_name'=>trim($params['cat_name']),
				'cat_logo'=>trim($params['cat_logo']),
				'sort_order'=>trim($params['sort_order']),
				'is_show'=>trim($params['is_show']),
				'is_floor'=>trim($params['is_floor']),
		);
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		
		if (!$this->DistributorGoodsCatDao->create($data)){
			throw new \Exception($this->DistributorGoodsCatDao->getError());
		}
		if ($data['cat_id'] > 0){
			$result = $this->DistributorGoodsCatDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->DistributorGoodsCatDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($params){
		//分组下有商品不能删除
		$map = array(
				'self_cat_id'=>$params['cat_id'],
				'a.distributor_id'=>$params['distributor_id']
		);
		$goods = $this->DistributorGoodsDao()->findRecord($map);
		if ($goods) throw new \Exception('请先删除分组下的商品');
		
		$clist = $this->getCatChilds($params['cat_id']);
		$result = $this->DistributorGoodsCatDao->delRecords($clist);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	//获取分组的所有子类（包含自己）
	public function getCatChilds($cat_id){
		$use_category = false;
		$cat_list = M('distributor_goods_cat')->getField('cat_id,parent_id');
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
	
	public function getAllList($map){
		$orderby = 'sort_order ASC, cat_id DESC';
		$list = $this->DistributorGoodsCatDao->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function selectAllList($map){
		$orderby = 'sort_order ASC, cat_id DESC';
		return $this->DistributorGoodsCatDao->searchAllRecords($map, $orderby);
	}
	
	public function getTreeList($map){
		$orderby = 'sort_order ASC, cat_id DESC';
		$list = $this->DistributorGoodsCatDao->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function getTopList($map){
		$map['parent_id'] = 0;
		$orderby = 'sort_order ASC, cat_id DESC';
		return $this->DistributorGoodsCatDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		foreach ($list as $k => $v) {
			
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		return $info;
	}
	
	private function DistributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
	
	private function GoodsCatDao() {
		return D('Common/Goods/GoodsCat');
	}
}//end HelpService!甜品