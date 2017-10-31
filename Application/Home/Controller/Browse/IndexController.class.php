<?php
namespace Home\Controller\Browse;
use Home\Controller\BaseController;
use Common\Basic\Genre;

class IndexController extends BaseController {	
	public function _initialize(){
		//$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*客户浏览记录*/
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
		);
		$map['collect_type'] = array('in', array(Genre::CollectTypeGoodsFoot, Genre::CollectTypeStoryFoot));
		$params['map'] = $map;
		//店铺
		if (session('distributor_id')) {
			$params['distributor_id'] = session('distributor_id');
		}
		if (I('keyword')) {
			$params['keyword'] = I('keyword');
		}
		if (I('user_id')) {
			$params['user_id'] = I('user_id');
		}
		if (I('start_time')) {
			$params['start_time'] = I('start_time');
		}
		if (I('end_time')) {
			$params['end_time'] = I('end_time');
		}
		$result = $this->collectService()->getCollectList($params);
		$this->assign('list', $result['list']);
		$this->assign('count', $result['count']);
		
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//客户标签
		$user_list = $this->collectService()->selectDistinctUserid();
		$user_ids = array();
		if ($user_list) {
			foreach ($user_list as $v) {
				$user_ids[] = $v['user_id'];
			}
		}
		$map = array();
		if ($user_ids) {
			$map['user_id'] = array('in', $user_ids);
		}
		$user_list = $this->userService()->userAllList($map);
		$this->assign('user_list', $user_list);
		
		$this->assign('page_title', '客户浏览记录');
		$this->display();
    }
    
    public function infoAction($collect_id = 0) { /*NoPurview*/
    	$collect_info = $this->collectService()->getInfo($collect_id);
    	if (empty($collect_info)) $this->error('记录不存在');
    	if ($collect_info['collect_type'] == Genre::CollectTypeStoryFoot) {
    		$collect_info['url'] = DK_DOMAIN.'/wap/index.php/story/index/info/id/'.$collect_info['id_value'].'.html';
    	}elseif ($collect_info['collect_type'] == Genre::CollectTypeGoodsFoot) {
    		$collect_info['url'] = DK_DOMAIN.'/wap/index.php/mall/goods/info/id/'.$collect_info['id_value'].'.html';
    	}
    	$this->assign('info', $collect_info);
    	
    	//商品
    	$goods_info = $this->distributorGoodsService()->getInfo($collect_info['id_value']);
    	$this->assign('goods', $goods_info);
    	
    	//用户
    	$user_info = $this->userService()->getUserInfo($collect_info['user_id']);
    	$this->assign('user', $user_info);
    	
    	$this->assign('page_title', '客户浏览记录详情');
    	$this->display();
    }
    
	private function collectService(){
		return D('Collect', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
}