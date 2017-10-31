<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;

class CatController extends FController {
	protected $distributorGoodsCatService;
	
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'goods_cat_index',
		);
		$this->sbset($set);
		
		$this->distributorGoodsCatService = D('Distributor\GoodsCat', 'Service');
    }
	
    public function indexAction(){
    	$map = array('distributor_id'=>$this->org_id);
    	$orderby = 'sort_order ASC';
    	$categorys = $this->distributorGoodsCatService->getTrList($map, $orderby);
		$this->assign('categorys', $categorys);
		
		$this->display();
    }
    
    public function platformAction(){
    	$categorys = $this->distributorGoodsCatService->getPlatformList();
    	$this->assign('categorys', $categorys);
    	
    	$set = array(
    			'in'=>'goods',
    			'ac'=>'goods_cat_platform',
    	);
    	$this->sbset($set)->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->distributorGoodsCatService->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->editPublic();
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$map = array(
				'distributor_id'=>$this->org_id,
				'cat_id'=>$cat_id
		);
		$info = $this->distributorGoodsCatService->getInfo($map);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){			
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->distributorGoodsCatService->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->editPublic($cat_id);
		
		//是否有子类
		$map = array(
				'distributor_id'=>$this->org_id,
				'parent_id'=>$cat_id
		);
		$child_cat = $this->distributorGoodsCatService->getInfo($map);
		$this->assign('child_cat', $child_cat);
		
		$this->display();
	}
	
	private function editPublic($cat_id = 0) {
		//分类列表
		/* $categorys = $this->distributorGoodsCatService->getOptionList($cat_id);
		$this->assign('categorys', $categorys); */
		
		//分类树
		$params = array(
				'distributor_id'=>$this->org_id,
		);
		$cat_list = $this->distributorGoodsCatService->getTopList($params);
		$new_cat_list = array();
		foreach ($cat_list as $v) {
			if ($v['cat_id'] != $cat_id) {
				$new_cat_list[] = $v;
			}
		}
		$this->assign('cat_list', $new_cat_list);
		
		//商品标签
		/* $params = array(
				'distributor_id'=>$this->org_id,
		);
		$label_list = $this->goodsLabelService()->getAllList($params);
		$this->assign('label_list', $label_list); */
	}
	
	public function delAction($cat_id = 0){
		try {
			$params = array(
					'cat_id'=>$cat_id,
					'distributor_id'=>$this->org_id,
			);
			$this->distributorGoodsCatService->delete($params);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function goodsLabelService() {
		return D('GoodsLabel', 'Service');
	}
}