<?php
namespace Common\Service;
use Common\Model\Distributor\GoodsProductModel;

class GoodsProductService{
	protected $GoodsProductDao;
	
	public function __construct(){
		$this->GoodsProductDao = D('Common/Goods/GoodsProduct');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->GoodsProductDao->getRecord($id);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->GoodsProductDao->create($data)){
			 throw new \Exception($this->GoodsProductDao->getError());
		}
		if ($data['product_id'] > 0){
			$result = $this->GoodsProductDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			M()->startTrans();
			
			$result = $this->GoodsProductDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//设置商品表的货品数量
			$map = array('goods_id'=>$data['goods_id']);
			$res = $this->GoodsDao()->where($map)->setInc('product_num');
			if (!$res){
				M()->rollback();
				throw new \Exception('设置货品数量失败');
			}
			
			M()->commit();
		}
	}
	
	public function delete($id){
		$product = $this->GoodsProductDao->getRecord($id);
		if (empty($product)) {
			throw new \Exception('货品不存在');
		}
		
		M()->startTrans();
		
		$result = $this->GoodsProductDao->delRecord($id);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		//设置商品表的货品数量
		$map = array('goods_id'=>$product['goods_id']);
		$res = $this->GoodsDao()->where($map)->setDec('product_num');
		if (!$res){
			M()->rollback();
			throw new \Exception('设置货品数量失败');
		}
		
		M()->commit();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] =1000;
		
		$map = $params['map'];
		$count = $this->GoodsProductDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'product_id DESC' : $params['orderby'];
			$list = $this->GoodsProductDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($params){
		$map = $params['map'] ? $params['map'] : array();
		if ($params['goods_id']) {
			$map['goods_id'] = $params['goods_id'];
		}
		
		$count = $this->GoodsProductDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'product_id DESC' : $params['orderby'];
			$list = $this->GoodsProductDao->searchAllRecords($map, $orderby);
		}
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
		);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$sku_list = $this->goodsSkuDao()->getField('sku_id, sku_value');
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
	
	private function GoodsDao() {
		return D('Common/Goods/Goods');
	}
	
	private function goodsSkuDao() {
		return D('Common/Goods/GoodsSku');
	}
}//end HelpService!甜品