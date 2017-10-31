<?php
namespace Wap\Controller\Lottery;
use Wap\Controller\WapController;
use Common\Basic\Pager;
use Common\Logic\PointLogic;

class IndexController extends WapController {
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '抽奖');
    }
    
    public function indexAction() {
    	$this->display();
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
		$url=U('game/index/exchange',array('log_id'=>$result['log_id']));
    	$this->success('操作成功', '', array('txt'=>$result['prize_result'], 'angle'=>$result['angle'],'link'=>$url,'type'=>$result['prize_type']));
    }
    
    private function createAuto($params){
		
    }
	
    private function lotteryService(){
    	return D('Lottery', 'Service');
    }
}