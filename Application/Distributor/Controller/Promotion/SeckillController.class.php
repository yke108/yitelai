<?php
namespace Distributor\Controller\Promotion;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class SeckillController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'promotion',
			'ac'=>'promotion_seckill_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	$this->assign('page_title', '秒杀商品');
    	$this->listDisplay();
    }
    
    public function seckillingAction(){
    	session('back_url', __SELF__);
    	$this->sbset('promotion_seckill_seckilling');
    	$this->assign('page_title', '秒杀中商品');
    	$map = array(
    			'a.seckill_status'=>1,
    			'a.seckill_start'=>array('elt', NOW_TIME),
    			'a.seckill_end'=>array('egt', NOW_TIME)
    	);
    	$this->listDisplay($map);
    }
    
    public function finishAction(){
    	session('back_url', __SELF__);
    	$this->sbset('promotion_seckill_finish');
    	$this->assign('page_title', '秒杀结束商品');
    	$map['_string'] = 'a.seckill_status=2 OR a.seckill_start>='.time().' OR a.seckill_end<='.time();
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()) {
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	$params = array(
    			'distributor_id'=>$this->org_id
    	);
    	if ($map) {
    		$params['map'] = $map;
    	}
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	 
    	$datas = $this->distributorGoodsSeckillService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display('index');
    }
    
    public function addAction(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['distributor_id'] = $this->org_id;
    		$post['seckill_status'] = 1;
    		try {
    			$this->distributorGoodsSeckillService()->createOrModify($post);
    			$this->success('添加成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->display('edit');
    }
    
	public function viewAction($seckill_id = 0){
		$map = array(
				'seckill_id'=>$seckill_id,
				'distributor_id'=>$this->org_id
		);
		$info = $this->distributorGoodsSeckillService()->findInfo($map);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function goodsselAction(){
		layout('Layout/sel');
		
		$get = I('get.');
		$this->assign('get', $get);
		 
		//平台分类列表
		$categorys = $this->goodsCatService()->getOptionList($get['cat_id']);
		$this->assign('platform_categorys', $categorys);
		
		//自定义分类列表
		$map = array('distributor_id'=>$this->org_id);
		$categorys = $this->distributorGoodsCatService()->getOptionList($map, $get['self_cat_id']);
		$this->assign('distributor_categorys', $categorys);
		 
		//商品列表
		$params = array(
				'distributor_id'=>$this->org_id,
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
		);
		if (!empty($get['keyword'])) {
			$params['keyword'] = $get['keyword'];
		}
		if (!empty($get['cat_id'])) {
			$params['cat_id'] = $get['cat_id'];
		}
		if (!empty($get['self_cat_id'])) {
			$params['self_cat_id'] = $get['self_cat_id'];
		}
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		 
		$this->display();
	}
	
	public function productselAction($record_id = 0) {
		layout('Layout/sel');
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//商品信息
		/* $map = array(
				'record_id'=>$record_id,
				'a.distributor_id'=>$this->org_id
		);
		$info = $this->distributorGoodsService()->findInfo($map);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info); */
		
		//货品列表
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>array('record_id'=>$record_id),
		);
		$datas = $this->distributorGoodsProductService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display();
	}
	
	public function productlistAction($record_id = 0) {
		layout('Layout/sel');
		
		//货品列表
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>array('record_id'=>$record_id),
		);
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$this->assign('product_list', $product_list);
		if(empty($product_list)){
			$clist = '';
		}else{
			$clist = $this->renderPartial('_productlist');
		}
		$this->ajaxReturn($clist);
	}
	
	public function setStatusAction($seckill_id = 0) {
		try {
			$this->distributorGoodsSeckillService()->setStatus($seckill_id);
			$this->success('关闭成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function distributorGoodsSeckillService() {
		return D('Distributor\GoodsSeckill', 'Service');
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function distributorGoodsService() {
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsCatService() {
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function distributorGoodsProductService() {
		return D('Distributor\GoodsProduct', 'Service');
	}
}