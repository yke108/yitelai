<?php
namespace News\Controller\User;
use News\Controller\WapController;
use Common\Basic\Genre;

class HistoryController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '浏览记录');
    }
	
    public function indexAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'user_id'=>$this->user['user_id'],
		);
		$result = $this->newsReadService()->infoPagerList($params);
		$this->assign('list', $result['list']);
		
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
    		$res = $this->newsReadService()->del($params);
    		$this->success('删除成功');
    	} catch (\Exception $e){
    		$this->error($e->getMessage());
    	}
    }
	
	private function newsReadService(){
		return D('Information\NewsRead', 'Service');
	}
}