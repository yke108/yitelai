<?php
namespace Common\Service\Distributor;

class GoodsSeckillService{
	protected $distributorSeckillGoodsDao;
	
	public function __construct(){
		$this->distributorSeckillGoodsDao = D('Common/Distributor/SeckillGoods');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->distributorSeckillGoodsDao->getRecord($id);
	}
	
	public function findInfo($map){
		$info = $this->distributorSeckillGoodsDao->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	private function outputForInfo($info) {
		if (empty($info)) {
			return $info;
		}
		
		$map = array('seckill_id'=>$info['seckill_id']);
		$product_list = $this->distributorSeckillGoodsProductDao()->searchAllRecords($map);
		$info['product_list'] = $this->outputForList($product_list);
		
		return $info;
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//处理数据
		$data['seckill_start'] = strtotime($data['seckill_start']);
		$data['seckill_end'] = strtotime($data['seckill_end']);
		
		//检查数据
		if ($data['seckill_start'] >= $data['seckill_end']) {
			throw new \Exception('开始时间必须小于结束时间');
		}
		if ($data['seckill_end'] <= NOW_TIME) {
			throw new \Exception('结束时间不能小于当前时间');
		}
		if (empty($params['checkid'])) {
			throw new \Exception('请选择货品');
		}
		
		//分销商商品
		$distributor_goods = $this->distributorGoodsDao()->getRecord($data['record_id']);
		if ($distributor_goods['seckill_start'] <= NOW_TIME && $distributor_goods['seckill_end'] >= NOW_TIME && $distributor_goods['seckill_status'] == 1 && $distributor_goods['total_seckill_num'] > 0) {
			throw new \Exception('秒杀商品已存在');
		}
		
		//处理数据
		M()->startTrans();
		
		//添加秒杀记录
		$data['goods_image'] = $distributor_goods['goods_image'];
		if (!$this->distributorSeckillGoodsDao->create($data)){
			M()->rollback();
			throw new \Exception($this->distributorSeckillGoodsDao->getError());
		}
		$seckill_id = $this->distributorSeckillGoodsDao->add();
		if ($seckill_id < 1){
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		//分销商货品
		$total_seckill_num = 0;
		$map = array('record_id'=>$distributor_goods['record_id']);
		$distributor_product_list = $this->distributorGoodsProductDao()->searchAllRecords($map);
		foreach ($distributor_product_list as $distributor_product) {
			//修改货品秒杀价格
			$map = array(
					'id'=>$distributor_product['id']
			);
			$seckill_price = $seckill_num = $is_seckill = 0;
			if (in_array($distributor_product['id'], $params['checkid'])) {
				$seckill_price = $params['seckill_price'][$distributor_product['id']];
				if ($seckill_price <= 0) {
					throw new \Exception('秒杀价格不能小于0');
				}
				$seckill_num = $params['seckill_num'][$distributor_product['id']];
				if ($seckill_num <= 0) {
					throw new \Exception('秒杀数量不能小于0');
				}
				//秒杀货品
				$dataList[] = array(
						'seckill_id'=>$seckill_id,
						'distributor_product_id'=>$distributor_product['id'],
						'product_name'=>$distributor_product['product_name'],
						'product_image'=>$distributor_product['product_image'],
						'seckill_price'=>$params['seckill_price'][$distributor_product['id']],
						'seckill_num'=>$params['seckill_num'][$distributor_product['id']],
				);
				$is_seckill = 1;
			}
			$data_product = array(
					'seckill_price'=>$seckill_price,
					'seckill_num'=>$seckill_num,
					'is_seckill'=>$is_seckill
			);
			$result = $this->distributorGoodsProductDao()->where($map)->save($data_product);
			if ($result === false){
				M()->rollback();
				throw new \Exception('设置秒杀价格失败');
			}
			
			$total_seckill_num += $seckill_num;
		}
		
		//记录秒杀货品
		$result = $this->distributorSeckillGoodsProductDao()->addAll($dataList);
		if ($result === false){
			M()->rollback();
			throw new \Exception('记录秒杀货品失败');
		}
		
		//设置商品秒杀时间
		$map = array(
				'record_id'=>$distributor_goods['record_id']
		);
		$data = array(
				'seckill_start'=>$data['seckill_start'],
				'seckill_end'=>$data['seckill_end'],
				'seckill_status'=>1,
				'total_seckill_num'=>$total_seckill_num
		);
		$result = $this->distributorGoodsDao()->where($map)->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('设置秒杀时间失败');
		}
		
		M()->commit();
	}
	
	public function delete($id){
		$distributor_product = $this->distributorSeckillGoodsDao->getRecord($id);
		if (empty($distributor_product)) {
			throw new \Exception('货品不存在');
		}
		
		M()->startTrans();
		
		$result = $this->distributorSeckillGoodsDao->delRecord($id);
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
	
	public function setStatus($id){
		$sec_info = $this->distributorSeckillGoodsDao->find($id);
		
		M()->startTrans();
		
		if ($this->distributorSeckillGoodsDao->where(array('seckill_id'=>$id))->save(array('seckill_status'=>2)) === false) {
			M()->rollback();
			throw new \Exception('设置失败');
		}
		
		$data = array(
				'seckill_status'=>2,
				'seckill_start'=>0,
				'seckill_end'=>0
		);
		if ($this->distributorGoodsDao()->where(array('record_id'=>$sec_info['record_id']))->save($data) === false) {
			M()->rollback();
			throw new \Exception('设置失败');
		}
		
		if ($this->distributorGoodsProductDao()->where(array('record_id'=>$sec_info['record_id']))->save(array('seckill_price'=>0,'is_seckill'=>0)) === false) {
			M()->rollback();
			throw new \Exception('设置失败');
		}
		
		M()->commit();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (isset($params['distributor_id'])) {
			$map['a.distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['keyword'])) {
			$map['goods_name'] = array('like', '%'.$params['keyword'].'%');
		}
		
		$count = $this->distributorSeckillGoodsDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'seckill_id DESC' : $params['orderby'];
			$list = $this->distributorSeckillGoodsDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
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
		
		return $list;
	}
	
	private function GoodsProductDao() {
		return D('Common/Goods/GoodsProduct');
	}
	
	private function distributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
	
	private function distributorGoodsProductDao() {
		return D('Common/Distributor/GoodsProduct');
	}
	
	private function distributorSeckillGoodsProductDao() {
		return D('Common/Distributor/SeckillGoodsProduct');
	}
	
	private function goodsSkuDao() {
		return D('Common/Goods/GoodsSku');
	}
}//end HelpService!甜品