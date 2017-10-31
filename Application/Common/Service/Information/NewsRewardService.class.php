<?php
namespace Common\Service\Information;
use Common\Basic\Status;
use Common\Basic\Genre;

class NewsRewardService{
	public function createReward($params){
		//自动验证
		$rules = array(
				array('pay_id', 'require', '打赏金额不能为空'),
				array('pay_id',array(1,2,3),'支付方式不正确',2,'in'),
				array('reward_amount', 'require', '打赏金额不能为空'),
		);
		//参数/
		$reward_id = date('ymdHis').rand(1000,9999);
		$data = array(
				'reward_id'=>$reward_id,
				'user_id'=>$params['user_id'],
				'news_id'=>$params['news_id'],
				'pay_id'=>$params['pay_id'],
				'reward_amount'=>$params['reward_amount'],
				'add_time'=>NOW_TIME
		);
		
		$newsRewardDao = $this->newsRewardDao();
		if (!$newsRewardDao->validate($rules)->create($data)){
			throw new \Exception($newsRewardDao->getError());
		}
		$reward_id = $newsRewardDao->addRecord($data);
		if ($reward_id < 1){
			throw new \Exception('添加失败');
		}
		return $reward_id;
	}
	
	public function rewardPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['pay_status']) && $map['pay_status'] = $params['pay_status'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		if ($params['keyword']) {
			$map['story_title'] = array('like', '%'.$params['keyword'].'%');
			$storys = $this->newsInfoDao()->allRecords($map);
			if (empty($storys)) {
				return array();
			}
			$news_ids = array();
			foreach ($storys as $v) {
				$news_ids[] = $v['news_id'];
			}
			$map['news_id'] = array('in', $news_ids);
		}
		if ($params['cat_id']) {
			$clist = $this->getCatChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
			$storys = $this->newsInfoDao()->allRecords($map);
			if (empty($storys)) {
				return array();
			}
			$news_ids = array();
			foreach ($storys as $v) {
				$news_ids[] = $v['news_id'];
			}
			$map['news_id'] = array('in', $news_ids);
		}
		
		$newsRewardDao = $this->newsRewardDao();
		$count = $newsRewardDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'reward_id DESC' : $params['orderby'];
			$list = $newsRewardDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		$result = $this->_rewardList($list, $params);
		return array(
				'list'=>$result['list'],
				'count'=>$count,
				'admins'=>$result['admins'],
		);
	}
	
	private function _rewardList($list) {
		if (!empty($list)) {
			//文章
			$user_ids = $news_ids = $admin_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$news_ids[] = $v['news_id'];
				if($v['admin_id'] > 0){
					$admin_ids[$v['admin_id']] = $v['admin_id'];
				}
			}
			
			//打赏者
			$users = $this->userInfoDao()->getUsers($user_ids);
			
			//文章
			$map['news_id'] = array('in', $news_ids);
			$news = $this->newsInfoDao()->searchFieldRecords($map);
			
			//作者
			$author_ids = array();
			foreach ($news as $v) {
				$author_ids[] = $v['user_id'];
			}
			$authors = $this->userInfoDao()->getUsers($author_ids);
			
			//管理员
			if(!empty($admin_ids)){
				$admins = $this->adminInfoDao()->getFieldRecord(array('admin_id'=>array('in',$admin_ids)));
			}
			
			foreach ($list as $k => $v) {
				$list[$k]['title'] = $news[$v['news_id']]['title'];
				$list[$k]['picture'] = $news[$v['news_id']]['picture'];
				
				$list[$k]['author_name'] = $authors[$news[$v['news_id']]['user_id']]['nick_name'];
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
			}
		}
		
		return array('list'=>$list, 'admins'=>$admins);
	}
	
	public function rewardInfo($params){
		$map = array('reward_id'=>$params['reward_id']);
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['pay_status']) && $map['pay_status'] = $params['pay_status'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$newsRewardDao = $this->newsRewardDao();
		$info = $newsRewardDao->findRecord($map);
		
		return $info;
	}
	
	//打赏
	public function payReward($params){
		//打赏人
		$user_info = $this->userInfoDao()->getRecord($params['user_id']);
		//判断余额是否足够
		if ($params['pay_id'] == 1) {
			if ($params['reward_amount'] > $user_info['user_money']) throw new \Exception('余额不足');
		}
		
		M()->startTrans();
		
		//减少账户余额
		$result = $this->userInfoDao()->depleteMoney($user_info['user_id'], $params['reward_amount']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//打赏人
		$result = $this->userInfoDao()->increasePayReward($user_info['user_id'], $params['reward_amount']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		$params_account = array(
				'user_id'=>$user_info['user_id'],
				'amount_old'=>$user_info['user_money'],
				'amount_change'=>$params['reward_amount'],
				'ref_user_id'=>$params['author_id'],
				'ref_id'=>$params['news_id'],
		);
		$result = $this->userAccountDao()->payReward($params_account);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//支付成功
		try {
			$this->paySuccess($params);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		M()->commit();
		
		return true;
	}
	
	public function paySuccess($params) {
		//打赏记录
		$reward_info = $this->newsRewardDao()->getRecord($params['reward_id']);
		//文章
		$news_info = $this->newsInfoDao()->getRecord($reward_info['news_id']);
		//作者
		$author = $this->userInfoDao()->getRecord($news_info['author_id']);
		
		//作者收入
		$result = $this->userInfoDao()->increaseUserMoney($author['user_id'], $reward_info['reward_amount']);
		if ($result === false) {
			throw new \Exception('支付失败');
		}
		$result = $this->userInfoDao()->increaseGetReward($author['user_id'], $reward_info['reward_amount']);
		if ($result === false) {
			throw new \Exception('支付失败');
		}
		$params_account = array(
				'user_id'=>$author['user_id'],
				'amount_old'=>$author['user_money'],
				'amount_change'=>$reward_info['reward_amount'],
				'author_id'=>$reward_info['user_id'],
				'ref_id'=>$news_info['news_id'],
		);
		$result = $this->userAccountDao()->getReward($params_account);
		if ($result === false) {
			throw new \Exception('支付失败');
		}
		
		//修改支付状态
		$map = array('reward_id'=>$params['reward_id']);
		$data = array('pay_status'=>1, 'pay_time'=>NOW_TIME);
		$result = $this->newsRewardDao()->updateRecord($map, $data);
		if ($result === false) {
			throw new \Exception('支付失败');
		}
		
		//统计打赏数
		$reward_info = $this->newsRewardDao()->getRecord($params['reward_id']);
		$result = $this->newsInfoDao()->where(array('news_id'=>$reward_info['news_id']))->setInc('reward_count');
		if ($result === false) {
			throw new \Exception('支付失败');
		}
	}
	
	private function newsInfoDao(){
		return D('Common/Information/News/Info');
	}
	
	private function newsRewardDao(){
		return D('Common/Information/News/Reward');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
}//end HelpService!甜品