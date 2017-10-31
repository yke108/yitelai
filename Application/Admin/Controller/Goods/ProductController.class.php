<?php
namespace Admin\Controller\Goods;
use Admin\Controller\FController;
use Common\Basic\Pager;

class ProductController extends FController {
	protected $GoodsProductService;
	protected $GoodsService;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'goods',
			'ac'=>'goods_product_index',
		);
		$this->sbset($set);
		
		$this->GoodsProductService = D('GoodsProduct', 'Service');
		$this->GoodsService = D('Goods', 'Service');
    }
    
    public function indexAction($goods_id = 0){
    	//商品信息
    	$info = $this->GoodsService->getInfo($goods_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>array('goods_id'=>$goods_id),
		);
		$datas = $this->GoodsProductService->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		session('back_url', __SELF__);
		
		$this->display();
    }
	
	public function addAction($goods_id = 0){
		$goods_info = $this->GoodsService->getInfo($goods_id);
		if (empty($goods_info)) {
			$this->error('商品不存在');
		}
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->GoodsProductService->createOrModify($post);
				$back_url = session('back_url');
				session('back_url', null);
				$this->success('添加成功', $back_url);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$info['goods_id'] = $goods_id;
		$this->assign('info', $info);
		$this->display('edit');
	}
	
	public function editAction($product_id = 0){
		$info = $this->GoodsProductService->getInfo($product_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->GoodsProductService->createOrModify($post);
				$back_url = session('back_url');
				session('back_url', null);
				$this->success('编辑成功', $back_url);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($product_id = 0){
		try {
			$this->GoodsProductService->delete($product_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
}