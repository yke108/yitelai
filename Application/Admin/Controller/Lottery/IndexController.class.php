<?php
namespace Admin\Controller\Lottery;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'lottery',
			'ac'=>'lottery_index_index',
		);
		$this->sbset($set);
    }
	
	public function indexAction($id = 0){
		$this->purviewCheck('lottery_index_index');
		$get = I('get.');
		$params = array(
			'page'=>$get['p'],
			'pagesize'=>20,
			'orderby'=>'add_time desc',
		);
		$result = $this->lotteryService()->infoPagerList($params);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $result['pagesize']);
			$this->assign('page', $pager->show());
		}
		$this->display();
    }
	
	public function addAction(){
		$this->purviewCheck('lottery_index_add');
		if(IS_POST){
			try {
				$post = I('post.');
				$params = array(
					'lottery_name'=>$post['title'],
					'lottery_awards'=>$post['awards'],
					'start_time'=>$post['start_time'],
					'end_time'=>$post['end_time'],
					'is_open'=>$post['is_open'],
					'can_use_point'=>$post['can_use_point'],
					'day_times'=>$post['day_times'],
					'play_points'=>$post['play_points'],
						'lottery_brief'=>$post['lottery_brief'],
					
				);
				$this->lotteryService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功');
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$this->purviewCheck('lottery_index_edit');
		if(IS_POST){
			$post = I('post.');
			try {
				$params = array(
					'lottery_id'=>$id,
					'lottery_name'=>$post['title'],
					'lottery_awards'=>$post['awards'],
					'start_time'=>$post['start_time'],
					'end_time'=>$post['end_time'],
					'is_open'=>$post['is_open'],
					'can_use_point'=>$post['can_use_point'],
					'day_times'=>$post['day_times'],
					'play_points'=>$post['play_points'],
						'lottery_brief'=>$post['lottery_brief'],
				);
				$this->lotteryService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功');
		}
		$info = $this->lotteryService()->getInfo($id);
		$info['lottery_awards'] = json_decode($info['lottery_awards'], true);
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$this->purviewCheck('lottery_index_del');
		try {
			$this->lotteryService()->infoDelete($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function logAction($lottery_id = 0){
		$this->purviewCheck('lottery_index_log');
		$get = I('get.');
		$params = array(
			'page'=>$get['p'],
			'pagesize'=>20,
			'orderby'=>'play_time desc',
			'lottery_id'=>$lottery_id,
		);
		$result = $this->lotteryService()->logPagerList($params);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $result['pagesize']);
			$this->assign('page', $pager->show());
			$this->assign('user_list', $result['user_list']);
			$this->assign('prize_type_list', $result['prize_type_list']);
			$this->assign('regions', $result['regions']);
		}
		$this->display();
    }
	
    private function lotteryService(){
    	return D('Lottery', 'Service');
    }
}