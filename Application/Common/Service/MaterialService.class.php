<?php
namespace Common\Service;
use Common\Basic\Genre;

class MaterialService{
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->materialInfoDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->materialInfoDao()->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function getFileInfo($id){
		if ($id < 1) return false;
		return $this->materialFileDao()->getRecord($id);
	}
	
	public function viewCount($id){
		$this->materialInfoDao()->where(array('material_id'=>$id))->setInc('view_count');
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
		
		$result = $this->materialInfoDao()->where(array('material_id'=>$params['material_id']))->setInc('collect_count');
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
		
		$result = $this->materialInfoDao()->where(array('material_id'=>$params['material_id']))->setDec('collect_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('取消失败');
		}
		
		M()->commit();
		
		return array('collect_count'=>$info['collect_count'] - 1);
	}
	
	public function downCount($id, $user_id){
		$this->materialInfoDao()->where(array('material_id'=>$id))->setInc('down_count');
		$info = $this->getInfo($id);
		$this->designerInfoDao()->where(array('material_id'=>$info['designer_id']))->setInc('down_count');
		$data = array(
				'user_id'=>$user_id,
				'material_id'=>$info['material_id'],
				'down_points'=>$info['down_points'],
				'add_time'=>NOW_TIME,
		);
		$this->materialDownDao()->add($data);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//相册
		$data['material_gallery'] = trim($data['material_gallery'], '#');
		//文件名称
		if (empty($data['file_name'])) {
			$file_name = pathinfo($data['upload_path']);
			$data['file_name'] = $file_name['basename'];
		}
		//设置
		$data['is_show'] = $params['is_show'] ? $params['is_show'] : 0;
		//标签
		$data['label'] = $params['label'] ? implode(',', $params['label']) : '';
		
		M()->startTrans();
		
		if ($data['material_id'] > 0){
			$info = $this->materialInfoDao()->getRecord($data['material_id']);
			if (empty($info)) throw new \Exception('修改失败');
			
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
			
			//如果分类改变了，就删除原来的属性值
			if ($info['cat_id'] != $data['cat_id']) {
				$map = array(
						'material_id'=>$data['material_id'],
				);
				$result = M('material_spec_values')->where($map)->delete();
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
			}
			
			if (!$this->materialInfoDao()->create($data)){
				throw new \Exception($this->materialInfoDao()->getError());
			}
			$result = $this->materialInfoDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
			
			$material_id = $data['material_id'];
		} else {
			//关联用户ID
			$admin_info = $this->adminInfoDao()->getRecord($data['admin_id']);
			$data['user_id'] = $admin_info['user_id'] ? $admin_info['user_id'] : '';
			
			$data['material_id'] = date('ymdHis').rand(1000,9999);
			if (!$this->materialInfoDao()->create($data)){
				throw new \Exception($this->materialInfoDao()->getError());
			}
			$material_id = $this->materialInfoDao()->add();
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
		
		//处理素材属性
		$spec_value_ids = array();
		foreach ($params['material_spec'] as $k => $v) {
			$map = array(
					'material_id'=>$material_id,
					'spec_id'=>$k,
			);
			$material_spec = M('material_spec_values')->where($map)->find();
			if ($material_spec) {
				$result = M('material_spec_values')->where($map)->save(array('spec_value'=>trim($v)));
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
				$spec_value_ids[] = $material_spec['material_spec_value_id'];
			}else {
				$data = array(
						'material_id'=>$material_id,
						'spec_id'=>$k,
						'spec_value'=>trim($v)
				);
				$spec_value_id = M('material_spec_values')->add($data);
				if ($spec_value_id === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
				$spec_value_ids[] = $spec_value_id;
			}
		}
		if ($spec_value_ids) {
			$data = array(
					'spec_value_ids'=>implode(',', $spec_value_ids)
			);
			$result = $this->materialInfoDao()->where(array('material_id'=>$material_id))->save($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		}
		
		//修改文件
		if ($params['files']) {
			$file_ids = array();
			foreach ($params['files'] as $k => $v) {
				if (empty($v['upload_path'])) throw new \Exception('素材图片不能为空');
				if (empty($v['file_name'])) throw new \Exception('素材名称不能为空');
				if (empty($v['file_size'])) throw new \Exception('素材尺寸不能为空');
				
				$data = array(
						'file_id'=>$k,
						'upload_path'=>$v['upload_path'],
						'file_name'=>$v['file_name'],
						'file_size'=>$v['file_size'],
				);
				$result = $this->materialFileDao()->saveRecord($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('系统错误');
				}
				$file_ids[] = $k;
			}
			
			//删除文件
			$map = array('file_id'=>array('not in', $file_ids));
			$result = $this->materialFileDao()->where($map)->delete();
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		//添加文件
		if ($params['path']) {
			$dataList = array();
			foreach ($params['path'] as $k => $v) {
				if (empty($v)) throw new \Exception('素材图片不能为空');
				if (empty($params['file_name'][$k])) throw new \Exception('素材名称不能为空');
				if (empty($params['file_size'][$k])) throw new \Exception('素材尺寸不能为空');
				
				$dataList[] = array(
						'material_id'=>$material_id,
						'upload_path'=>$v,
						'file_name'=>$params['file_name'][$k],
						'file_size'=>$params['file_size'][$k],
				);
			}
			$result = $this->materialFileDao()->addAll($dataList);
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		M()->commit();
	}
	
	public function delete($params){
		M()->startTrans();
		
		$map = array(
				'material_id'=>$params['material_id'],
				'distributor_id'=>$params['distributor_id'],
		);
		$info = $this->findInfo($map);
		if (empty($info)) throw new \Exception('素材不存在');
		
		//统计分类
		$result = $this->reduceCatCount($info['cat_id']);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		//删除素材
		$result = $this->materialInfoDao()->delRecord($info['material_id']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		M()->commit();
	}
	
	public function delall($params){
		$map = array(
				'material_id'=>array('in',$params['material_ids']),
				'distributor_id'=>$params['distributor_id'],
		);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialInfoDao()->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->reduceCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('删除失败');
			}
		}
		
		//批量删除
		$result = $this->materialInfoDao()->delRecords($map);
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
		if (isset($params['admin_id'])) {
			$map['admin_id'] = $params['admin_id'];
		}
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
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
		
		$count = $this->materialInfoDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'material_id DESC' : $params['orderby'];
			$list = $this->materialInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list, $params),
			'count'=>$count,
		);
	}
	
	public function getCount($map){
		return $this->materialInfoDao()->searchRecordsCount($map);
	}
	
	public function getDownCount($map){
		return $this->materialInfoDao()->where($map)->sum('down_count');
	}
	
	public function getDownList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		
		$count = $this->materialDownDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$list = $this->materialDownDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
			foreach ($list as $v) {
				$material_ids[] = $v['material_id'];
			}
			$map = array('material_id'=>array('in', $material_ids));
			$material_list = $this->materialInfoDao()->searchFieldRecords($map);
			
			foreach ($list as $k => $v) {
				$list[$k]['material_title'] = $material_list[$v['material_id']]['material_title'];
				$list[$k]['url'] = DK_DOMAIN.'/material/index.php/index/index/info/id/'.$v['material_id'].'.html';
			}
		}
		
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function getYesterdayDownCount(){
		$map['_string'] = "TO_DAYS( NOW( ) ) - TO_DAYS(add_time) <= 1";
		return $this->materialInfoDao()->where($map)->count();
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
				if ($v['material_gallery']) {
					$list[$k]['material_gallery'] = explode('#', $v['material_gallery']);
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
				
				//文件
				$map = array('material_id'=>$v['material_id']);
				$list[$k]['file_list'] = $this->materialFileDao()->searchAllRecords($map);
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
			
			$filename= picurl($info['upload_path']);
			$img_info = pathinfo($filename);
			$info['mime'] = $img_info['extension'];
			
			$file = UPLOAD_PATH.$info['upload_path'];
			$length = filesize($file);
			$info['bits'] = round($length / 1024);
			
			//商品属性
			$info['spec_list'] = M('material_spec_values')->alias('a')->field('a.*, b.spec_name')
								->join('LEFT JOIN __MATERIAL_SPEC__ b ON b.spec_id=a.spec_id')
								->where(array('material_id'=>$info['material_id']))->select();
			
			//文件
			$map = array('material_id'=>$info['material_id']);
			$info['file_list'] = $this->materialFileDao()->searchAllRecords($map);
		}
		
		return $info;
	}
	
	public function up($params) {
		$map = array(
				'material_id'=>array('in',$params['material_ids']),
				'distributor_id'=>$params['distributor_id'],
		);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialInfoDao()->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->increaseCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		//批量上架
		$data = array('is_show'=>1);
		$result = $this->materialInfoDao()->where($map)->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception($this->materialInfoDao()->getError());
		}
		
		M()->commit();
	}
	
	public function down($params) {
		$map = array(
				'material_id'=>array('in',$params['material_ids']),
				'distributor_id'=>$params['distributor_id'],
		);
		
		M()->startTrans();
		
		//分类统计
		$material_list = $this->materialInfoDao()->searchAllRecords($map);
		foreach ($material_list as $v) {
			$result = $this->reduceCatCount($v['cat_id']);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		//批量下架
		$data = array('is_show'=>0);
		$result = $this->materialInfoDao()->where($map)->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception($this->materialInfoDao()->getError());
		}
		
		M()->commit();
	}
	
	public function recommend($params){
		$map = array(
				'material_id'=>$params['material_id'],
				'distributor_id'=>$params['distributor_id'],
		);
		$info = $this->findInfo($map);
		if(empty($info)) throw new \Exception('素材不存在');
		
		$data = array('material_id'=>$info['material_id'], 'is_recommend'=>($info['is_recommend'] == 1 ? 0 : 1));
		$result = $this->materialInfoDao()->save($data);
		if($result === false) throw new \Exception('推荐失败');
	}
	
	public function show($params){
		$map = array(
				'material_id'=>$params['material_id'],
				'distributor_id'=>$params['distributor_id'],
		);
		$info = $this->findInfo($map);
		if(empty($info)) throw new \Exception('素材不存在');
		
		M()->startTrans();
		
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
		
		$data = array('material_id'=>$info['material_id'], 'is_show'=>($info['is_show'] == 1 ? 0 : 1));
		$result = $this->materialInfoDao()->save($data);
		if($result === false) {
			M()->rollback();
			throw new \Exception('操作失败');
		}
		
		M()->commit();
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
	
	public function get_filter_attr($cat_id, $get) {
		$filter_attr_str = isset($get['filter_attr']) ? htmlspecialchars(trim($get['filter_attr'])) : '0';
		$filter_attr_str = trim(urldecode($filter_attr_str));
		$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
		$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
		
		$cat = $this->materialCatDao()->find($cat_id);
		$cat_filter_attr = $this->materialSpecDao()->field('spec_id, spec_name')->where(array('type_id'=>$cat['type_id']))->select();
		
		$all_attr_list = array();
		foreach ($cat_filter_attr AS $key => $value)
		{
			$map = array(
					'spec_id'=>$value['spec_id'],
					'spec_value'=>array('neq', ''),
					//'goods_spec_value_id'=>array('not in', $filter_attr)
			);
			$attr_list = $this->materialSpecValuesDao()->field('material_spec_value_id, material_id, spec_id, spec_value')->where($map)->group('spec_value')->select();
				
			if (empty($attr_list)) {
				continue;
			}
				
			$temp_arrt_url_arr = array();
			for ($i = 0; $i < count($cat_filter_attr); $i++)
			{
				$temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
			}
			
			$temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
			$temp_arrt_url = implode('.', $temp_arrt_url_arr);
			$all_attr_list[$key]['attr_list'][0]['attr_value'] = '全部';
			$all_attr_list[$key]['attr_list'][0]['url'] = U('index/index/list', array('id'=>$cat['cat_id'], 'is_free'=>$get['is_free'], 'sort_order'=>$get['sort_order'], 'filter_attr'=>$temp_arrt_url));
			$all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;
			
			$temp_arrt_url_arr[$key] = 0;
			$temp_arrt_url = implode('.', $temp_arrt_url_arr);
			$all_attr_list[$key]['spec_id'] = $value['spec_id'];
					$all_attr_list[$key]['attr_value'] = $value['spec_name'];
							$url = U('index/index/list', array('id'=>$cat['cat_id'], 'is_free'=>$get['is_free'], 'sort_order'=>$get['sort_order'], 'filter_attr'=>$temp_arrt_url));
							$all_attr_list[$key]['url'] = $url;
							//$all_attr_list[$key]['selected'] = empty($filter_attr[$key]) ? 1 : 0;
							$all_attr_list[$key]['selected'] = 0;
								
							foreach ($attr_list as $k => $v)
							{
								$temp_key = $k + 1;
								$temp_arrt_url_arr[$key] = $v['material_spec_value_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
								$temp_arrt_url = implode('.', $temp_arrt_url_arr);
	
								$all_attr_list[$key]['attr_list'][$temp_key]['material_spec_value_id'] = $v['material_spec_value_id'];
								$all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['spec_value'];
								$url = U('index/index/list', array('id'=>$cat['cat_id'], 'is_free'=>$get['is_free'], 'is_free'=>$get['is_free'], 'filter_attr'=>$temp_arrt_url));
								$all_attr_list[$key]['attr_list'][$temp_key]['url'] = $url;
									
								if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['material_spec_value_id'])
								{
									$all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
									$all_attr_list[$key]['selected'] = 1;
								}
								else
								{
									$all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
								}
							}
		}
		//_p($all_attr_list);
		return $all_attr_list;
	}
	
	private function materialInfoDao() {
		return D('Common/Material/Info');
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
	
	private function materialSpecDao() {
		return D('Common/Material/Spec');
	}
	
	private function materialFileDao() {
		return D('Common/Material/File');
	}
	
	private function materialSpecValuesDao() {
		return D('Common/Material/SpecValues');
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
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
}//end HelpService!甜品