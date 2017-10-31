<?php
namespace Home\Controller\Resource;
use Home\Controller\BaseController;

class ActivityController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*门店活动 */
    	$result = $this->distributorActivityService()->infoAllList();
    	$list = array();
    	foreach ($result as $k => $v) {
    		$year = date('Y', $v['add_time']);
    		$month = date('m', $v['add_time']);
    		$list[$year][$month][] = $v;
    	}
    	$this->assign('list', $list);
    	
    	$this->assign('page_title', '门店活动');
		$this->display();
    }
    
    public function addAction() { /*NoPurview*/
    	$get = I('get.');
    	if ($get['record_id']) {
    		$record_ids = $get['record_ids'].','.$get['record_id'];
    		$url = U('', array('record_ids'=>$record_ids));
    		header('Location:'.$url);
    	}
    	
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->distributorActivityService()->infoCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	$this->assign('get', $get);
    	
    	//分类
    	//$map = array('distributor_id'=>session('admin_id'));
    	$orderby = 'sort_order ASC';
    	$categorys = $this->distributorGoodsCatService()->getOptionList($map, $select_id);
    	$this->assign('categorys', $categorys);
    	
    	//商品列表
    	if ($get['record_ids']) {
    		$map = array();
    		$map['a.record_id'] = array('in', $get['record_ids']);
    		//$map['a.distributor_id'] = session('admin_id');
    		$goods_list = $this->distributorGoodsService()->goodsAllList($map);
    		$this->assign('goods_list', $goods_list);
    	}
    	
    	$this->assign('page_title', '发布活动');
    	$this->display();
    }
    
    public function goods_listAction() { /*NoPurview*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品列表
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>session('admin_id'),
    	);
    	if ($get['record_ids']) {
    		$map['record_id'] = array('not in', $get['record_ids']);
    		$params['map'] = $map;
    	}
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$result = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_goods_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$this->assign('page_title', '选择主推产品');
    	$this->display();
    }
    
    public function infoAction($activity_id = 0) { /*NoPurview*/
    	$info = $this->distributorActivityService()->getInfo($activity_id);
    	if (empty($info)) $this->error('活动不存在');
    	$this->assign('info', $info);
    	
    	//主推系列
    	$map = array('cat_id'=>array('in', $info['cat_ids']));
    	$cat_list = $this->distributorGoodsCatService()->selectAllList($map);
    	$this->assign('cat_list', $cat_list);
    	
    	//主推产品
    	$map = array();
    	$map['a.record_id'] = array('in', $info['record_ids']);
    	$goods_list = $this->distributorGoodsService()->goodsAllList($map);
    	$this->assign('goods_list', $goods_list);
    	
    	$this->assign('page_title', '门店活动-详情');
    	$this->display();
    }
    
    private function distributorActivityService(){
    	return D('Distributor\Activity', 'Service');
    }
    
    private function distributorGoodsCatService(){
    	return D('Distributor\GoodsCat', 'Service');
    }
    
    private function distributorGoodsService(){
    	return D('Distributor\Goods', 'Service');
    }
}