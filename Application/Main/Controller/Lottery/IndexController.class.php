<?php
namespace Main\Controller\Lottery;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Logic\PointLogic;

class IndexController extends MainController {
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '抽奖');
    }
	
    public function logAction(){
    	$get = I('get.');
    	$params = array(
    		'user_id'=>$this->user['user_id'],
    		'lottery_id'=>$get['lottery_id'],
    	);
    	
    	try {
    		$result = $this->lotteryService()->logResult($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage(), '', array('code'=>$e->getCode()));
    	}
    	$this->success('操作成功', '', array('txt'=>$result['prize_result'], 'angle'=>$result['angle']));
    }
    
    private function createAuto($params){
		
    }
	
    private function lotteryService(){
    	return D('Lottery', 'Service');
    }
}