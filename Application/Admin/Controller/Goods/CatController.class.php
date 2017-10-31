<?php
namespace Admin\Controller\Goods;
use Admin\Controller\FController;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'goods_cat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$categorys = $this->goodsCatService()->getTrList();
		$this->assign('categorys', $categorys);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->goodsCatService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//分类列表
		$categorys = $this->goodsCatService()->getOptionList();
		$this->assign('categorys', $categorys);
		
		//商品场景
		$scene_list = $this->goodsSceneService()->getAllList();
		$this->assign('scene_list', $scene_list);
		
		//商品标签
		$label_list = $this->goodsLabelService()->getAllList();
		$this->assign('label_list', $label_list);
		
		//商品类型
		$type_list = $this->goodsTypeService()->getAllList();
		$this->assign('type_list', $type_list);
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->goodsCatService()->getInfo($cat_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->goodsCatService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//分类列表
		$cat_ids = $this->goodsCatService()->getCatChilds($info['cat_id']);
		$map['cat_id'] = array('not in', $cat_ids);
		$categorys = $this->goodsCatService()->getOptionList($info['parent_id'], $map);
		$this->assign('categorys', $categorys);

		//商品场景
		$scene_list = $this->goodsSceneService()->getAllList();
		$this->assign('scene_list', $scene_list);
		
		//商品标签
		$label_list = $this->goodsLabelService()->getAllList();
		$this->assign('label_list', $label_list);
		
		//商品类型
		$type_list = $this->goodsTypeService()->getAllList();
		$this->assign('type_list', $type_list);
		
		$this->display();
	}
	
	public function delAction($cat_id = 0){
		try {
			$this->goodsCatService()->delete($cat_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function goodsSceneService() {
		return D('GoodsScene', 'Service');
	}
	
	private function goodsLabelService() {
		return D('GoodsLabel', 'Service');
	}
	
	private function goodsTypeService() {
		return D('GoodsType', 'Service');
	}
}