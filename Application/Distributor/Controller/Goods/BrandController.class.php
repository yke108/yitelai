<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;

class BrandController extends FController {
	protected $GoodsBrandService;
	
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'sb_goods',
			'ac'=>'sb_goods_brand_index',
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
		$this->assign('pager', $datas['pager']);
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
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->GoodsBrandService->getInfo($id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->GoodsBrandService->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$id = intval($id);
		$map = array(
			'id'=>$id,
		);
		$info = M('Category')->where($map)->find();
		if(empty($info)){
			$this->error('分类不存在');
		}
		
		//是否有下级分类
		$info = M('Category')->where('parent_id=%d', $id)->find();
		if(!empty($info)){
			$this->error('含有下级分类的分类不能直接删除');
		}
		
		//是否有关联的商品
		$info = M('Goods')->where('cat_id=%d',$id)->find();
		if(!empty($info)){
			$this->error('已含有商品的分类不能直接删除');
		}
		
		M('Category')->delete($id);
		$this->success('删除成功');
	}
	
}