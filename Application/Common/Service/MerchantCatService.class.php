<?php
namespace Common\Service;

class MerchantCatService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->merchantCatDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->merchantCatDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function addCat($params) {
		//三级类目
		$cat = $this->goodsCatDao()->getRecord($params['cat_ids'][0]);
		//二级类目
		$map = array('cat_id'=>$cat['parent_id']);
		$parent_cat = $this->goodsCatDao()->findRecord($map);
		//一级类目
		$map = array('cat_id'=>$parent_cat['parent_id']);
		$top_cat = $this->goodsCatDao()->findRecord($map);
		//如果主营类目改变，则删除之前添加的类目
		if ($top_cat['cat_id'] != $params['cat_id']) {
			$map = array('merchant_id'=>$params['merchant_id'], 'user_id'=>$params['user_id']);
			$result = $this->merchantCatDao()->delRecords($map);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//更新主营类目
			$map = array('merchant_id'=>$params['merchant_id'], 'user_id'=>$params['user_id']);
			$data = array('cat_id'=>$top_cat['cat_id']);
			$result = $this->merchantDao()->where($map)->save($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//排除已添加的
		$map = array('merchant_id'=>$params['merchant_id'], 'user_id'=>$params['user_id']);
		$cat_list = $this->getAllList($map);
		$cat_ids = array();
		foreach ($cat_list as $v) {
			$cat_ids[] = $v['cat_id'];
		}
		
		//接收到的参数
		$data = array();
		foreach ($params['cat_ids'] as $k => $v) {
			$cat_id = trim($v);
			if (!in_array($cat_id, $cat_ids)) {
				$data[] = array(
						'merchant_id'=>$params['merchant_id'],
						'user_id'=>$params['user_id'],
						'cat_id'=>$cat_id,
						'add_time'=>NOW_TIME
				);
			}
		}
		if ($data) {
			$result = $this->merchantCatDao()->addAll($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		return true;
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->merchantCatDao()->create($data)){
			 throw new \Exception($this->merchantCatDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['id'] > 0){
			$result = $this->merchantCatDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$id = $this->merchantCatDao()->add();
			if ($id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($map){
		$result = $this->merchantCatDao()->where($map)->delete();
		if (!$result){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['keyword'])) {
			$map['distributor_name'] = array('like', '%'.$params['keyword'].'%');
		}
		
		$count = $this->merchantCatDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id DESC' : $params['orderby'];
			$list = $this->merchantCatDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map, $orderby){
		$orderby = empty($orderby) ? 'id DESC' : $orderby;
		$list = $this->merchantCatDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($id){
		$info = $this->merchantCatDao()->find($id);
		if (empty($info)) throw new \Exception('数据不存在');
		$is_show = $info['is_show'] == 1 ? 0 : 1;
		$result = $this->merchantCatDao()->where(array('id'=>$id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k=> $v) {
				//三级类目
				$cat = $this->goodsCatDao()->getRecord($v['cat_id']);
				$list[$k]['cat_name'] = $cat['cat_name'];
				//二级类目
				$map = array('cat_id'=>$cat['parent_id']);
				$parent_cat = $this->goodsCatDao()->findRecord($map);
				$list[$k]['parent_cat_name'] = $parent_cat['cat_name'];
				//一级类目
				$map = array('cat_id'=>$parent_cat['parent_id']);
				$top_cat = $this->goodsCatDao()->findRecord($map);
				$list[$k]['top_cat_name'] = $top_cat['cat_name'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			;
		}
		
		return $info;
	}
	
	private function merchantCatDao(){
		return D('Common/Merchant/MerchantCat');
	}
	
	private function goodsCatDao(){
		return D('Common/Goods/GoodsCat');
	}
	
	private function merchantDao(){
		return D('Common/Merchant/Merchant');
	}
}//end HelpService!甜品