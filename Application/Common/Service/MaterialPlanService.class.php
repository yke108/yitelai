<?php
namespace Common\Service;
use Common\Basic\Genre;

class MaterialPlanService{
	protected $materialPlanDao;
	
	public function __construct(){
		$this->materialPlanDao = D('Common/Material/Plan');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->materialPlanDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function viewCount($id){
		$this->materialPlanDao->where(array('material_id'=>$id))->setInc('view_count');
	}
	
	public function collect($params){
		if (empty($params['user_id'])) throw new \Exception('请先登录');
		
		$info = $this->getInfo($params['material_id']);
		if (empty($info)) throw new \Exception('文件不存在');
		
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['material_id'],
				'collect_type'=>Genre::CollectTypeMaterial,
		);
		$collect_info = $this->collectDao()->getRecordInfo($map);
		if ($collect_info) throw new \Exception('已收藏');
		
		M()->startTrans();
		
		$data = $map;
		$data['add_time'] = NOW_TIME;
		$result = $this->collectDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('收藏失败');
		}
		
		$result = $this->materialPlanDao->where(array('material_id'=>$params['material_id']))->setInc('collect_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('收藏失败');
		}
		
		M()->commit();
		
		return array('collect_count'=>$info['collect_count'] + 1);
	}
	
	public function unCollect($params){
		if (empty($params['user_id'])) throw new \Exception('请先登录');
		
		$info = $this->getInfo($params['material_id']);
		if (empty($info)) throw new \Exception('文件不存在');
		
		M()->startTrans();
		
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['material_id'],
				'collect_type'=>Genre::CollectTypeMaterial,
		);
		$result = $this->collectDao()->delRecord($map);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('取消失败');
		}
		
		$result = $this->materialPlanDao->where(array('material_id'=>$params['material_id']))->setDec('collect_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('取消失败');
		}
		
		M()->commit();
		
		return array('collect_count'=>$info['collect_count'] - 1);
	}
	
	public function downCount($id, $user_id){
		$this->materialPlanDao->where(array('material_id'=>$id))->setInc('down_count');
		$info = $this->getInfo($id);
		$this->designerInfoDao()->where(array('material_id'=>$info['designer_id']))->setInc('down_count');
		$data = array(
				'user_id'=>$user_id,
				'material_id'=>$info['material_id'],
				'add_time'=>NOW_TIME,
		);
		$this->materialDownDao()->add($data);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//相册
		$data['material_gallery'] = trim($data['material_gallery'], '#');
		//设置
		$data['is_show'] = $params['is_show'] ? $params['is_show'] : 0;
		//标签
		$data['label'] = $params['label'] ? implode(',', $params['label']) : '';
		
		M()->startTrans();
		
		if ($data['material_id'] > 0){
			//统计分类
			$info = $this->getInfo($data['material_id']);
			if ($info['cat_id'] != $data['cat_id']) {
				//原分类
				$result = $this->reduceCatCount($info['cat_id']);
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
				
				//新分类
				$result = $this->increaseCatCount($params['cat_id']);
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
			}
			
			if (!$this->materialPlanDao->create($data)){
				throw new \Exception($this->materialPlanDao->getError());
			}
			$result = $this->materialPlanDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$data['material_id'] = date('ymdHis').rand(1000,9999);
			if (!$this->materialPlanDao->create($data)){
				throw new \Exception($this->materialPlanDao->getError());
			}
			$material_id = $this->materialPlanDao->add();
			if ($material_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//统计分类
			$result = $this->increaseCatCount($data['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
	}
	
	public function delete($id){
		M()->startTrans();
		
		$info = $this->getInfo($id);
		if (empty($info)) throw new \Exception('素材不存在');
		
		//统计分类
		$result = $this->reduceCatCount($info['cat_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		$result = $this->materialPlanDao->delRecord($id);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		M()->commit();
	}
	
	public function delall($material_ids){
		$map['material_id'] = array('in',$material_ids);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialPlanDao->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->reduceCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('删除失败');
			}
		}
		
		//批量删除
		$result = $this->materialPlanDao->delRecords($map);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		M()->commit();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (isset($params['keyword'])) {
			$map['material_title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (isset($params['cat_id'])) {
			$clist = $this->materialCatService()->getCatChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['designer_id'])) {
			$map['designer_id'] = $params['designer_id'];
		}
		if (isset($params['label_id'])) {
			$map['label'] = array('like', '%'.$params['label_id'].'%');
		}
		if (isset($params['is_show'])) {
			$map['is_show'] = $params['is_show'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		
		$count = $this->materialPlanDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'material_id DESC' : $params['orderby'];
			$list = $this->materialPlanDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list, $params),
			'count'=>$count,
		);
	}
	
	public function getCount($map){
		return $this->materialPlanDao->searchRecordsCount($map);
	}
	
	public function getDownCount($map){
		return $this->materialPlanDao->where($map)->sum('down_count');
	}
	
	public function getYesterdayDownCount(){
		$map['_string'] = "TO_DAYS( NOW( ) ) - TO_DAYS(add_time) <= 1";
		return $this->materialPlanDao->where($map)->count();
	}
	
	private function outputForList($list, $params = array()) {
		if (!empty($list)) {
			$cat_ids = $id_values = $designer_ids = array();
			foreach ($list as $v) {
				$cat_ids[] = $v['cat_id'];
				$id_values[] = $v['material_id'];
				$designer_ids[] = $v['designer_id'];
			}
			//分类
			$material_cats = $this->materialCatDao()->searchRecordsField($cat_ids);
			//设计师
			$designers = $this->designerInfoDao()->getIdsRecord($designer_ids);
			//收藏
			$map = array(
					'user_id'=>$params['user_id'],
					'collect_type'=>Genre::CollectTypeMaterial,
					'id_values'=>$id_values
			);
			$collects = $this->collectDao()->searchAllRecords($map);
			$collect_ids = array();
			foreach ($collects as $v) {
				$collect_ids[] = $v['id_value'];
			}
			
			foreach ($list as $k => $v) {
				//相册
				if ($v['goods_gallery']) {
					$list[$k]['goods_gallery'] = explode('#', $v['goods_gallery']);
				}
				
				//标签
				if ($v['label']) {
					$list[$k]['label'] = explode(',', $v['label']);
					
					$map = array('label_id'=>array('in', $v['label']));
					$list[$k]['label_list'] = $this->materialLabelDao()->searchAllRecords($map);
				}
				
				//分类
				$list[$k]['cat_name'] = $material_cats[$v['cat_id']];
				
				//设计师
				$list[$k]['designer_name'] = $designers[$v['designer_id']]['designer_name'];
				$list[$k]['designer_image'] = $designers[$v['designer_id']]['designer_image'];
				
				//预览
				$url = U("material/index/preview", array("id"=>$v['material_id']));
				$url = str_replace("/gajadmin","",$url);
				$list[$k]['url'] = $url;
				
				//收藏
				if (in_array($v['material_id'], $collect_ids)) {
					$list[$k]['is_collect'] = 1;
				}else {
					$list[$k]['is_collect'] = 0;
				}
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			//分类
			$material_cat = $this->materialCatDao()->getRecord($info['cat_id']);
			$info['cat_name'] = $material_cat['cat_name'];
			
			//相册
			if (!empty($info['material_gallery'])) {
				$info['material_gallery'] = explode('#', $info['material_gallery']);
			}
			
			//标签
			if (!empty($info['label'])) {
				$info['label'] = explode(',', $info['label']);
				
				$map = array('label_id'=>array('in', $info['label']));
				$info['label_list'] = $this->materialLabelDao()->searchAllRecords($map);
			}
			
			$filename= picurl($info['material_image']);
			$img_info = getimagesize($filename);
			$info['mime'] = $img_info['mime'];
			
			$file = UPLOAD_PATH.$info['material_image'];
			$length = filesize($file);
			$info['bits'] = round($length / 1024);
		}
		
		return $info;
	}
	
	public function up($material_ids) {
		$map['material_id'] = array('in', $material_ids);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialPlanDao->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->increaseCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		//批量上架
		$data = array('is_show'=>1);
		$result = $this->materialPlanDao->where($map)->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception($this->materialPlanDao->getError());
		}
		
		M()->commit();
	}
	
	public function down($material_ids) {
		$map['material_id'] = array('in', $material_ids);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialPlanDao->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->reduceCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		//批量下架
		$data = array('is_show'=>0);
		$result = $this->materialPlanDao->where($map)->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception($this->materialPlanDao->getError());
		}
		
		M()->commit();
	}
	
	public function recommend($material_id){
		$info = $this->getInfo($material_id);
		if(empty($info)) throw new \Exception('推荐失败');
		$data = array('material_id'=>$material_id, 'is_recommend'=>($info['is_recommend'] == 1 ? 0 : 1));
		$result = $this->materialPlanDao->save($data);
		if($result === false) throw new \Exception('推荐失败');
	}
	
	public function show($material_id){
		$info = $this->getInfo($material_id);
		if(empty($info)) throw new \Exception('操作失败');
		
		//分类统计
		if ($info['is_show'] == 1) {
			$result = $this->reduceCatCount($info['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}else {
			$result = $this->increaseCatCount($info['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		$data = array('material_id'=>$material_id, 'is_show'=>($info['is_show'] == 1 ? 0 : 1));
		$result = $this->materialPlanDao->save($data);
		if($result === false) throw new \Exception('操作失败');
	}
	
	private function increaseCatCount($cat_id){
		$cat = $this->materialCatDao()->getRecord($cat_id);
		if (empty($cat)) return false;
		
		$map = array('cat_id'=>$cat['cat_id']);
		$result = $this->materialCatDao()->where($map)->setInc('material_count');
		if ($result === false) return false;
		
		if ($cat['parent_id'] > 0) {
			$parent = $this->materialCatDao()->getRecord($cat['parent_id']);
			if (empty($parent)) return false;
				
			$map = array('cat_id'=>$parent['cat_id']);
			$result = $this->materialCatDao()->where($map)->setInc('material_count');
			if ($result === false) return false;
		}
	}
	
	private function reduceCatCount($cat_id){
		$cat = $this->materialCatDao()->getRecord($cat_id);
		if (empty($cat)) return false;
		
		$map = array('cat_id'=>$cat['cat_id']);
		$result = $this->materialCatDao()->where($map)->setDec('material_count');
		if ($result === false) return false;
		
		if ($cat['parent_id'] > 0) {
			$parent = $this->materialCatDao()->getRecord($cat['parent_id']);
			if (empty($parent)) return false;
			
			$map = array('cat_id'=>$parent['cat_id']);
			$result = $this->materialCatDao()->where($map)->setDec('material_count');
			if ($result === false) return false;
		}
	}
	
	private function materialCatDao() {
		return D('Common/Material/Cat');
	}
	
	private function materialLabelDao() {
		return D('Common/Material/Label');
	}
	
	private function materialDownDao() {
		return D('Common/Material/Down');
	}
	
	private function designerInfoDao() {
		return D('Common/Designer/DesignerInfo');
	}
	
	private function collectDao() {
		return D('Common/Collect');
	}
	
	private function materialCatService() {
		return D('MaterialCat', 'Service');
	}
	
}//end HelpService!甜品