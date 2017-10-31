<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;
use Common\Basic\Genre;

class ShareController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction($user_id = 0) { /*NoPurview*/
    	//文章
    	$params = array(
    			'page'=>1,
    			'pagesize'=>$this->pagesize,
    			'user_id'=>$user_id,
    			'collect_type'=>Genre::CollectTypeShareStory,
    	);
    	$result = $this->collectService()->getCollectList($params);
    	$story_ids = $story_list = array();
    	foreach ($result['list'] as $v) {
    		$story_ids[] = $v['id_value'];
    	}
    	if ($story_ids) {
    		$map = array('story_id'=>array('in', $story_ids));
    		$list = $this->storyService()->infoAllList($map);
    		foreach ($list as $v) {
    			$v['collect_type'] = Genre::CollectTypeShareStory;
    			$story_list[] = $v;
    		}
    	}
    	$this->assign('story_list', $story_list);
    	
    	//商品
    	$params = array(
    			'page'=>1,
    			'pagesize'=>$this->pagesize,
    			'user_id'=>$user_id,
    			'collect_type'=>Genre::CollectTypeShareGoods,
    	);
    	$result = $this->collectService()->getCollectList($params);
    	$record_ids = $goods_list = array();
    	foreach ($result['list'] as $v) {
    		$record_ids[] = $v['id_value'];
    	}
    	if ($record_ids) {
    		$map = array('record_id'=>array('in', $record_ids));
    		$list = $this->distributorGoodsService()->goodsAllList($map);
    		foreach ($list as $v) {
    			$v['collect_type'] = Genre::CollectTypeShareGoods;
    			$goods_list[] = $v;
    		}
    	}
    	$this->assign('goods_list', $goods_list);
    	
    	$this->assign('page_title', '客户转发');
		$this->display();
    }
    
    public function listAction() {  /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'collect_type'=>I('collect_type'),
    			'user_id'=>I('user_id'),
    	);
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    private function collectService() {
    	return D('Collect', 'Service');
    }
    
    private function storyService() {
    	return D('Story', 'Service');
    }
    
    private function distributorGoodsService() {
    	return D('Distributor\Goods', 'Service');
    }
}
