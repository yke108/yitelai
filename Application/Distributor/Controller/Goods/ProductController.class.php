<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class ProductController extends FController {
	protected $distributorGoodsProductService;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'goods',
			'ac'=>'goods_product_index',
		);
		$this->sbset($set);
		
		$this->distributorGoodsProductService = D('Distributor\GoodsProduct', 'Service');
    }
    
    public function indexAction($record_id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品信息
    	$map = array(
    			'record_id'=>$record_id,
    			'a.distributor_id'=>$this->org_id
    	);
    	$info = $this->DistributorGoodsService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
		
		//货品列表
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>array('record_id'=>$record_id),
		);
		$datas = $this->distributorGoodsProductService->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		session('back_url', __SELF__);
		
		$this->display();
    }
    
    public function understockAction() {
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//货品列表
    	$map = array(
    			'c.distributor_id'=>$this->org_id
    	);
    	$map['_string'] = 'stock_num<=notify_num';
    	$params = array(
    			//'distributor_id'=>$this->org_id,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'map'=>$map,
    	);
    	$datas = $this->distributorGoodsProductService->getUnderstockPagerList($params);
    	
    	$list = array();
    	if ($datas['list']) {
    		foreach ($datas['list'] as $v) {
    			$record_ids[] = $v['record_id'];
    		}
    		
    		$map = array('record_id'=>array('in', $record_ids));
    		$record_list = $this->DistributorGoodsService()->goodsAllList($map);
    		$new_record_list = array();
    		foreach ($record_list as $v) {
    			$new_record_list[$v['record_id']] = $v;
    		}
    		
    		foreach ($datas['list'] as $v) {
    			$v['goods_name'] = $new_record_list[$v['record_id']]['goods_name'];
    			$list[] = $v;
    		}
    	}
    	
    	$this->assign('list', $list);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	session('back_url', __SELF__);
    	
    	$this->sbset('goods_product_understock');
    	$this->display();
    }
	
	public function editAction($id = 0){
		$get = I('get.');
		$this->assign('get', $get);
		
		//分销商货品
		$info = $this->distributorGoodsProductService->getInfo($id);
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			
			//库存数量不能超过平台库存的25%
			$stock_number = floor($info['stock_number'] * 0.25);
			if ($post['stock_num'] > $stock_number) {
				$this->error('库存数量不能超过'.$stock_number);
			}
			
			try {
				$this->distributorGoodsProductService->createOrModify($post);
				$back_url = session('back_url');
				session('back_url', null);
				$this->success('编辑成功', $back_url);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
	private function DistributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function GoodsService(){
		return D('Goods', 'Service');
	}
	
	private function GoodsProductService(){
		return D('GoodsProduct', 'Service');
	}
}