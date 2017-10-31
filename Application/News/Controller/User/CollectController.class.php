<?php
namespace News\Controller\User;
use News\Controller\WapController;
use Common\Basic\Genre;

class CollectController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>5,
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeNews,
		);
		$result = $this->newsCollectService()->getPagerList($params);
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
		
		$this->display();
    }
    
    public function delcollectAction($id = 0){
    	$params = array(
    			'collect_id'=>$id,
    			'user_id'=>session('userid'),
    	);
    	try {
    		$res = $this->newsCollectService()->del($params);
    		$this->success('删除成功');
    	} catch (\Exception $e){
    		$this->error($e->getMessage());
    	}
    }
	
	private function newsCollectService(){
		return D('Information\NewsCollect', 'Service');
	}
}