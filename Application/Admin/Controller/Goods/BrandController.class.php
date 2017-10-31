<?php
namespace Admin\Controller\Goods;
use Admin\Controller\FController;
use Common\Basic\Pager;

class BrandController extends FController {
	protected $GoodsBrandService;
	
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'goods_brand_index',
		);
		$this->sbset($set);
		
		$this->GoodsBrandService = D('GoodsBrand', 'Service');
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'get'=>$get,
    	);
    	$datas = $this->GoodsBrandService->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->GoodsBrandService->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$categorys = $this->goodsCatService()->getOptionList();
		$this->assign('categorys', $categorys);
		
		$this->display('edit');
	}
	
	public function editAction($brand_id = 0){
		$info = $this->GoodsBrandService->getInfo($brand_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->GoodsBrandService->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$categorys = $this->goodsCatService()->getOptionList($info['cat_id']);
		$this->assign('categorys', $categorys);
		
		$this->display();
	}
	
	public function delAction($brand_id = 0){
		try {
			$this->GoodsBrandService->delete($brand_id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function is_recommendAction($brand_id){
		try {
			$this->GoodsBrandService->isRecommend($brand_id);
			$this->success('推荐品牌成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');;
	}
}