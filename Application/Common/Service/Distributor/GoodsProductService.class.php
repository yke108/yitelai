<?php
namespace Common\Service\Distributor;

class GoodsProductService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->distributorGoodsProductDao()->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->distributorGoodsProductDao()->findRecord($map);
	}
	
	public function createOrModify($params){
		//检查数据
		if (intval($params['stock_num'] < $params['notify_num'])) {
			throw new \Exception('预警数量必须小于库存数量');
		}
		
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->distributorGoodsProductDao()->create($data)){
			 throw new \Exception($this->distributorGoodsProductDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['id'] > 0){
			$result = $this->distributorGoodsProductDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			//修改分销商商品价格
			$default_product = $this->getDefaultProduct($params['record_id']);
			$max_product = $this->getMaxProduct($params['record_id']);
			$map = array('record_id'=>$params['record_id']);
			$data = array(
					'min_product_price'=>$default_product['product_price'],
					'max_product_price'=>$max_product['product_price']
			);
			$res = $this->distributorGoodsDao()->where($map)->save($data);
			if ($res === false){
				M()->rollback();
				throw new \Exception('修改商品价格失败');
			}
			
		} else {
			$result = $this->distributorGoodsProductDao()->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
	}
	
	public function delete($id){
		$distributor_product = $this->distributorGoodsProductDao()->getRecord($id);
		if (empty($distributor_product)) {
			throw new \Exception('货品不存在');
		}
		
		M()->startTrans();
		
		$result = $this->distributorGoodsProductDao()->delRecord($id);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		//设置商品表的货品数量
		$map = array('record_id'=>$distributor_product['record_id']);
		$res = $this->distributorGoodsDao()->where($map)->setDec('product_num');
		if (!$res){
			M()->rollback();
			throw new \Exception('设置货品数量失败');
		}
		
		M()->commit();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		$count = $this->distributorGoodsProductDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id DESC' : $params['orderby'];
			$list = $this->distributorGoodsProductDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function getUnderstockPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
	
		$map = $params['map'] ? $params['map'] : array();
		$count = $this->distributorGoodsProductDao()->searchDistributorRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id DESC' : $params['orderby'];
			$list = $this->distributorGoodsProductDao()->searchDistributorRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
				'list'=>$this->outputForList($list, $list[0]['goods_id']),
				'count'=>$count,
		);
	}
	
	public function getAllList($params){
		$record_id = $params['record_id'] ? $params['record_id'] : $params['map']['record_id'];
		
		//平台货品
		$distributor_goods = $this->distributorGoodsDao()->getRecord($record_id);
		$map = array('goods_id'=>$distributor_goods['goods_id']);
		$platform_product_list = $this->goodsProductDao()->searchAllRecords($map);
		
		//货品列表
		$map = $params['map'] ? $params['map'] : array();
		if ($params['record_id']) {
			$map['record_id'] = $params['record_id'];
		}
		$orderby = empty($params['orderby']) ? 'id DESC' : $params['orderby'];
		$product_list = $this->distributorGoodsProductDao()->searchAllRecords($map, $orderby);
		
		//默认货品
		$default_product = $this->getDefaultProduct($record_id);
		$new_product_list = array();
		foreach ($product_list as $k => $v) {
			$v['is_default'] = ($v['id'] == $default_product['id']) ? 1 : 0;
			$new_product_list[$v['id']] = $v;
		}
		
		return $this->outputForList($new_product_list, $product_list[0]['goods_id']);
	}
	
	private function outputForList($list, $goods_id) {
		if (!empty($list)) {
			$sku_list = $this->goodsSkuDao()->where(array('goods_id'=>$goods_id))->getField('sku_id, sku_value');
			foreach ($list as $k => $v) {
				$product_items_name = '';
				if (!empty($v['product_items'])) {
					$product_items = explode(',', $v['product_items']);
					foreach ($product_items as $v2) {
						$product_items_name .= $sku_list[$v2].',';
					}
				}
				$list[$k]['product_items_name'] = trim($product_items_name, ',');
			}
		}
		
		return $list;
	}
	
	public function getDefaultProduct($record_id){
		if ($record_id < 1) return false;
		$map = array('record_id'=>$record_id);
		$orderBy = 'a.is_seckill DESC, a.product_price ASC';
		return $this->distributorGoodsProductDao()->searchRecord($map, $orderBy);
	}
	
	public function getMaxProduct($record_id){
		if ($record_id < 1) return false;
		$map = array('record_id'=>$record_id);
		$orderBy = 'a.product_price DESC';
		return $this->distributorGoodsProductDao()->searchRecord($map, $orderBy);
	}
	
	public function copyProduct($params){
		if (empty($params['product_ids'])) {
			throw new \Exception('请选择添加的货品');
		}
		$map['product_id'] = array('in',$params['product_ids']);
		$product_list = $this->goodsProductDao()->searchAllRecords($map);
		if (empty($product_list)) {
			throw new \Exception('货品不存在');
		}
		
		M()->startTrans();
		
		foreach ($product_list as $k => $v) {
			$dataList[] = array(
					'record_id'=>$params['record_id'],
					'product_id'=>$v['product_id'],
					'product_price'=>$v['product_price'],
					//'market_price'=>$v['market_price'],
					'add_time'=>NOW_TIME,
					'update_time'=>NOW_TIME,
					//'stock_num'=>$v['stock_num'],
					//'notify_num'=>$v['notify_num']
			);
		}
		$res = $this->distributorGoodsProductDao()->addAll($dataList);
		if (!$res) {
			M()->rollback();
			throw new \Exception($this->distributorGoodsProductDao()->getError());
		}
		
		//修改货品数量
		$res = $this->distributorGoodsDao()->where(array('record_id'=>$params['record_id']))->save(array('product_num'=>count($product_list)));
		if (!$res) {
			M()->rollback();
			throw new \Exception($this->distributorGoodsDao()->getError());
		}
		
		M()->commit();
		
		return true;
	}
	
	private function distributorGoodsProductDao() {
		return D('Common/Distributor/GoodsProduct');
	}
	
	private function goodsProductDao() {
		return D('Common/Goods/GoodsProduct');
	}
	
	private function distributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
	
	private function goodsSkuDao() {
		return D('Common/Goods/GoodsSku');
	}
}//end HelpService!甜品