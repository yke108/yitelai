<?php
namespace Main\Controller\Story;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Logic\PointLogic;
use Common\Payment\WeixinPay\AppPay;
use Common\Basic\Genre;

class IndexController extends MainController {
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '粉丝故事会');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$pagesize = 8;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>$pagesize,
    		'keyword'=>$get['keyword'],
    		'cat_id'=>$get['cat_id'],
    		'status'=>1,
			//'is_show'=>1,
    	);
		if($get['cat_id']==''){
			$params['is_top']=0;
		}
    	$result = $this->storyinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $pagesize);
    		$this->assign('pages', $pager->show_pc());
    	}
		
		if($get['cat_id']!=''){
			$cat_info=$this->storyinfoService()->getCat($get['cat_id']);
			$this->assign('cat_info',$cat_info);
		}
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
		
		//置顶文章
		//$params['is_top']=1;
		//$params['orderby']='sort_order desc';
		$params = array(
				'is_top'=>1,
				'orderby'=>'sort_order desc',
				'status'=>1,
				//'is_show'=>1,
				'pagesize'=>10,
		);
		$top_art_result=$this->storyinfoService()->infoPagerList($params);
		$this->assign('top_list',$top_art_result['list']);
		
    	//分类
		$cat_params=array('pagesize'=>100);
		$cat_list=$this->storyinfoService()->catlist($cat_params);
		//$cat_list=node_merge($cat_result,0,1,'cat_id');
		$this->assign('fans_catlist',$cat_list);
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//需求提报
		$story_count = $this->designerMessageService()->getCount();
		$this->assign('story_count', $story_count);
		
		//文章排行
		$ranking_params=array('pagesize'=>10,'status'=>1,'orderby'=>'view_num desc,good_num desc');
		$article_ranking_result = $this->storyinfoService()->infoPagerList($ranking_params);
		$this->assign('article_ranking_list',$article_ranking_result['list']);
		
		
		//作者排行
		$user_ranking_result=$this->storyinfoService()->userRanking();
		$this->assign('user_ranking',$user_ranking_result);
		
		//你可能会喜欢
		$goods_params=array('pagesize'=>4,'orderby'=>'rand()');
		$goods_result=$this->goodsService()->getPagerList($goods_params);
		$this->assign('like_list',$goods_result['list']);
		//print_r($goods_result);die();
		
		$this->display();
    }
    
    public function listAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$pagesize = 8;
    	$params = array(
    			'page'=>$get['p'],
    			'pagesize'=>$pagesize,
    			'keyword'=>$get['keyword'],
    			'cat_id'=>$get['cat_id'],
    			'status'=>1,
    			//'is_show'=>1,
    	);
    	if($get['cat_id']==''){
    		$params['is_top']=0;
    	}
    	$result = $this->storyinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $pagesize);
    		$this->assign('pages', $pager->show_pc());
    	}
    
    	if($get['cat_id']!=''){
    		$cat_info=$this->storyinfoService()->getCat($get['cat_id']);
    		$this->assign('cat_info',$cat_info);
    	}
    
    	//获取省份
    	$province=$this->regionService()->getChildList();
    	$this->assign('province',$province);
    	
    	//分类
    	$cat_params=array('pagesize'=>100);
    	$cat_list=$this->storyinfoService()->catlist($cat_params);
    	//$cat_list=node_merge($cat_result,0,1,'cat_id');
    	$this->assign('fans_catlist',$cat_list);
    
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    
    	//需求提报
    	$story_count = $this->designerMessageService()->getCount();
    	$this->assign('story_count', $story_count);
    
    	//文章排行
    	$ranking_params=array('pagesize'=>10,'status'=>1,'orderby'=>'view_num desc,good_num desc');
    	$article_ranking_result = $this->storyinfoService()->infoPagerList($ranking_params);
    	$this->assign('article_ranking_list',$article_ranking_result['list']);
    
    
    	//作者排行
    	$user_ranking_result=$this->storyinfoService()->userRanking();
    	$this->assign('user_ranking',$user_ranking_result);
    
    	//你可能会喜欢
    	$goods_params=array('pagesize'=>4,'orderby'=>'rand()');
    	$goods_result=$this->goodsService()->getPagerList($goods_params);
    	$this->assign('like_list',$goods_result['list']);
    	//print_r($goods_result);die();
    
    	$this->display();
    }
	
	public function infoAction($id = 0){
		//文章
		$map = array(
				'story_id'=>$id,
				'status'=>1,
				//'is_show'=>1,
		);
		$info = $this->storyinfoService()->findInfo($map);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		//作者
		$story_user = $this->userService()->getUserInfo($info['user_id']);
		$this->assign('story_user', $story_user);
		
		//浏览数+1
		$params = array(
				'story_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		try {
			$this->storyinfoService()->addViewNum($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		//记录文章足迹
		if ($this->user) {
			$agent_info = getAgentInfo();
			$browser = getbrowser();
			$data = array(
					'id_value'=>$id,
					'user_id'=>$this->user['user_id'],
					'collect_type'=>Genre::CollectTypeStoryFoot,
					'system'=>$agent_info['sys'],
					'browser'=>$browser['browser'],
					'version'=>$browser['version'],
			);
			try {
				$res = $this->collectService()->collect($data);
			} catch (\Exception $e) {
				//$this->error($e->getMessage());
			}
		}
		
		//阅读文章送积分
		$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
		$user_read_count = $this->user['read_count'] + 1;
		if ($user_read_count >= $point_config['read_num']['fval']) {
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point'=>$point_config['read']['fval'],
					'type'=>PointLogic::PointTypeRead,
					'ref_id'=>$info['story_id'],
			);
			$result = $this->pointService()->addUserPoint($params);
			if($result === false){
				$this->error('阅读文章送积分失败');
			}
			
			//用户阅读数清零
			$data = array('read_count'=>0);
			$result = $this->userService()->modify($this->user, $data);
			if($result === false){
				$this->error('阅读文章送积分失败');
			}
		}
		
		//上一篇
		$map = array(
				'story_id'=>array('gt', $id),
				'status'=>1,
				'is_show'=>1,
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$pre = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('pre', $pre);
		
		//下一篇
		$map = array(
				'story_id'=>array('lt', $id),
				'status'=>1,
				'is_show'=>1,
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$next = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('next', $next);
		
		//文章排行
		$ranking_params=array('pagesize'=>10,'status'=>1,'orderby'=>'view_num desc,good_num desc');
		$article_ranking_result = $this->storyinfoService()->infoPagerList($ranking_params);
		$this->assign('article_ranking_list',$article_ranking_result['list']);
		
		//作者排行
		$user_ranking_result=$this->storyinfoService()->userRanking();
		$this->assign('user_ranking',$user_ranking_result);
		
		//推荐品牌
		$brand_map=array('is_recommend'=>1);
		$brand_params=array('pagesize'=>6,'map'=>$brand_map);
		$brand_result=$this->goodsBrandService()->getPagerList($brand_params);
		$this->assign('brand_list',$brand_result['list']);
		
		//抽奖
		$lottery = $this->lotteryService()->getInfoForUser();
		$awards = array();
		$lottery['lottery_awards'] = json_decode($lottery['lottery_awards'], true);
		foreach ($lottery['lottery_awards'] as $vo){
			$awards[] = $vo['prize_name'];
		}
		$lottery['lottery_awards'] = $awards;
		$this->assign('lottery', $lottery);
		
		//抽奖记录
		$params = array(
			'lottery_id'=>$lottery['lottery_id'],
			'orderby'=>'play_time desc',
			'play_time'=>array('gt', 0),
			'prize_type'=>array('neq', 101),
			'pagesize'=>10,
		);
		$result = $this->lotteryService()->logPagerList($params);
		$this->assign('log_list', $result['list']);
		$this->assign('user_list', $result['user_list']);
		
		$this->display();
	}
	
	public function viewAction($id = 0){
		$map = array(
				'story_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		//浏览数+1
		storyAddViewNum($id);
		
		//上一篇
		$map = array(
				'story_id'=>array('gt', $id),
				'user_id'=>$this->user['user_id']
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$pre = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('pre', $pre);
		
		//下一篇
		$map = array(
				'story_id'=>array('lt', $id),
				'user_id'=>$this->user['user_id']
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$next = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('next', $next);
		
		$this->display('Story/Index/info');
	}
	
	public function likeAction(){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', U('index/site/login'));
		}
		$post = I('post.');
		try {
			$post['type'] = 1;
			$post['user_id'] = $this->user['user_id'];
			$good_num = $this->storyinfoService()->infoLike($post);
			
			//点赞获得积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point_old'=>$this->user['pay_points'],
					'point'=>$point_config['likes']['fval'],
					'type'=>PointLogic::PointTypeLike,
					//'ref_user_id'=>$this->user['user_id'],
					'ref_id'=>$post['story_id']
			);
			try {
				$this->pointService()->addUserPoint($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$this->success('点赞成功', '', array('good_num'=>$good_num));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function clapAction(){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', U('index/site/login'));
		}
		$post = I('post.');
		try {
			$post['type'] = 2;
			$post['user_id'] = $this->user['user_id'];
			$bad_num = $this->storyinfoService()->infoClap($post);
			$this->success('拍砖成功', '', array('bad_num'=>$bad_num));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//打赏
	public function rewardAction($id = 0){
		$get = I('get.');
		$this->assign('get', $get);
	
		$map = array(
				'story_id'=>$id,
				'status'=>1,
				//'is_show'=>1,
		);
		$story_info = $this->storyinfoService()->findInfo($map);
		if(empty($story_info)) $this->error('内容不存在');
		$story_user = $this->userService()->getUserInfo($story_info['user_id']);
		if(empty($story_user)) $this->error('作者不存在');
		
		if (IS_POST){
			$post = I('post.');
			
			if (empty($this->user)) {
				$this->error('请先登录', U('index/site/login'));
			}
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
			if ($post['reward_amount'] <= 0) {
				$this->error('请输入打赏金额');
			}
			
			$post['user_id'] = $this->user['user_id'];
			$post['story_id'] = $story_info['story_id'];
			$post['story_user_id'] = $story_user['user_id'];
			//生成订单
			try {
				$reward_id = $this->storyinfoService()->createReward($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$post['reward_id'] = $reward_id;
			if($post['pay_id'] == 1) { //余额支付
				try {
					$result = $this->storyinfoService()->payReward($post);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
				$this->success('打赏成功', U('success', array('id'=>$reward_id)));
			} elseif ($post['pay_id'] == 2) { //微信支付
				$this->success('正在转向微信支付页面', U('weixinpay', array('id'=>$reward_id)));
			} elseif ($post['pay_id'] == 3) { //支付宝支付
				
			}
		}
		
		$this->assign('info', $story_info);
		$this->assign('story_user', $story_user);
		
		$this->display();
	}
	
	/**
	 * 支付宝支付
	 * @param array 提交信息的数组
	 * @return mixed false or null
	 */
	public function alipayAction($id = 0) {
		$params = array(
				'reward_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->rewardInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		
		$payment = array(
				'body'=>'打赏文章',
				'payment_id'=>'1477383484800',
				'cur_money'=>'100',
		);
		\Common\Payment\Alipay::dopay($payment);
	}
	
	//微信支付
	public function weixinpayAction($id = 0){
		$params = array(
				'reward_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$reward_info = $this->storyinfoService()->rewardInfo($params);
		if (empty($reward_info)) {
			$this->error('数据不存在');
		}
		
		header("Content-type:text/html;charset=utf-8");
		//$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
		$wxconf = $this->configService()->findWeixinConfigs();
		$unifiedOrder = new AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
		$unifiedOrder->setParameter("body", "打赏文章");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $id);//商户订单号
		$unifiedOrder->setParameter("total_fee", $reward_info['reward_amount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/weixin/reward.php');//通知地址
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		$reward_info['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
		
		$this->assign('info', $reward_info);
		
		$this->display();
	}
	
	public function successAction($id = 0) {
		$params = array(
				'reward_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->rewardInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function paychkAction($id = 0) {
		$params = array(
				'reward_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->rewardInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		
		if ($info['pay_status'] == 1) {
			$this->success('支付成功', U('success', array('id'=>$id)));
		}
		$this->error('未支付');
	}
	
    private function lotteryService(){
    	return D('Lottery', 'Service');
    }
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function inquiryService(){
		return D('Inquiry', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
	
	private function goodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage', 'Service');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function collectService(){
		return D('Collect', 'Service');
	}
}