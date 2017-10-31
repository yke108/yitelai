<?php
namespace News\Controller\Index;
use News\Controller\WapController;
use Common\Basic\Genre;

class IndexController extends WapController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	//禁止的新闻
    	$map_forbid = $forbid_news_ids = array();
    	$map = array('user_id'=>$this->user['user_id']);
    	$forbid_list = $this->newsForbidService()->infoAllList($map);
    	foreach ($forbid_list as $v) {
    		$forbid_news_ids[] = $v['news_id'];
    	}
    	if ($forbid_news_ids) {
    		$map_forbid = array('news_id'=>array('not in', $forbid_news_ids));
    	}
    	
    	//推荐新闻
    	$params = array('is_open'=>1, 'is_recommend'=>1, 'type'=>0, 'map'=>$map_forbid);
    	$recommend_list = $this->newsService()->infoPagerList($params);
    	$this->assign('recommend_list', $recommend_list['list']);
    	
    	//新闻分类
    	$map = array('parent_id'=>0, 'type'=>0);
    	if (!empty($this->user['news_cat_ids'])) {
    		$map['cat_id'] = array('in', $this->user['news_cat_ids']);
    	}
    	$cat_list = $this->newsService()->catAllList($map);
    	foreach ($cat_list as $k => $v) {
    		$params = array('is_open'=>1, 'cat_id'=>$v['cat_id'], 'type'=>0, 'map'=>$map_forbid);
    		$news_list = $this->newsService()->infoPagerList($params);
    		$cat_list[$k]['news_list'] = $news_list['list'];
    	}
    	$this->assign('cat_list', $cat_list);
    	
    	//全部分类
    	$map = array(
    			'parent_id'=>0,
    			'type'=>0,
    	);
    	$cat_all_list = $this->newsService()->catAllList($map);
    	$this->assign('cat_all_list', $cat_all_list);
    	
    	$this->assign('page_title', '资讯');
		$this->display();
    }
    
    public function listAction(){
    	//禁止的新闻
    	$forbid_news_ids = array();
    	$map = array('user_id'=>$this->user['user_id']);
    	$forbid_list = $this->newsForbidService()->infoAllList($map);
    	foreach ($forbid_list as $v) {
    		$forbid_news_ids[] = $v['news_id'];
    	}
    	if ($forbid_news_ids) {
    		$map_forbid = array('news_id'=>array('not in', $forbid_news_ids));
    	}
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'is_open'=>1,
    			'type'=>0,
    			'map'=>$map_forbid,
    	);
    	if (intval(I('is_recommend')) > 0) {
    		$params['is_recommend'] = intval(I('is_recommend'));
    	}
    	if (intval(I('cat_id')) > 0) {
    		$params['cat_id'] = intval(I('cat_id'));
    	}
    	$result = $this->newsService()->infoPagerList($params);
    	$this->assign('news_list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function infoAction($id = 0){
    	//详情
    	$map = array(
    			'news_id'=>$id,
    			'is_open'=>1,
    			'type'=>0,
    	);
    	$info = $this->newsService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('新闻不存在');
    	}
    	$info['read_count']++;
    	$this->assign('info', $info);
    	
    	//浏览数
    	$agent_info = getAgentInfo();
    	$browser_info = getbrowser();
    	$params = array(
    			'news_id'=>$id,
    			'user_id'=>$this->user['user_id'],
    			'system'=>$agent_info['sys'],
    			'browser'=>$browser_info['browser'],
    			'version'=>$browser_info['version'],
    	);
    	try {
    		$result = $this->newsService()->read($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	//分享统计
    	if (IS_AJAX && session('userid')) {
    		$data = array(
    				'news_id'=>$id,
    				'user_id'=>$this->user['user_id'],
    		);
    		try {
    			$res = $this->newsService()->share($data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('分享成功');
    	}
    	
    	//点赞
    	$map = array(
    			'news_id'=>$info['news_id'],
    			'user_id'=>$this->user['user_id'],
    	);
    	$like_info = $this->newsLikeService()->findInfo($map);
    	$this->assign('like_info', $like_info);
    	
    	//相关新闻
    	$params = array(
    			'pagesize'=>3,
    			'cat_id'=>$info['cat_id'],
    			'map'=>array('news_id'=>array('neq', $info['news_id'])),
    			'is_open'=>1,
    			'type'=>0,
    	);
    	$datas = $this->newsService()->infoPagerList($params);
    	$this->assign('news_list', $datas['list']);
    	
    	//评论
    	$params = array(
    			'news_id'=>$info['news_id'],
    			'user_id'=>$this->user['user_id'],
    	);
    	$datas = $this->newsCommentService()->infoPagerList($params);
    	$this->assign('comment_list', $datas['list']);
    	$this->assign('comment_count', $datas['count']);
    	
    	//收藏
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'id_value'=>$info['news_id'],
    			'collect_type'=>Genre::CollectTypeNews,
    	);
    	$collect_info = $this->newsCollectService()->findInfo($params);
    	$this->assign('collect_info', $collect_info);
    	
    	//微信分享
    	$share = array(
    			'title'=>$info['title'],
    			'desc'=>strip_tags(htmlspecialchars_decode($info['description'])),
    			'url'=>DK_DOMAIN.U('video/index/info', array('id'=>$info['news_id'])),
    			'img'=>picurl($info['picture']),
    	);
    	$this->assign('share', $share);
    	
    	$this->assign('page_title', '资讯详情');
    	$this->display();
    }
    
    public function comment_listAction(){
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'news_id'=>intval(I('news_id')),
    			'user_id'=>$this->user['user_id'],
    	);
    	$result = $this->newsCommentService()->infoPagerList($params);
    	$this->assign('comment_list', $result['list']);
    	 
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_comment_list');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function likeAction($id = 0){
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$news_info = $this->newsService()->getInfo($id);
    	if (empty($news_info)) {
    		$this->error('文章不存在');
    	}
    	
    	$map = array(
    			'news_id'=>$news_info['news_id'],
    			'user_id'=>$this->user['user_id'],
    	);
    	$like_info = $this->newsLikeService()->findInfo($map);
    	if (!empty($like_info)) {
    		$this->error('您已经点赞过');
    	}
    	
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'news_id'=>$news_info['news_id'],
    	);
    	try {
    		$result = $this->newsLikeService()->infoCreateOrModify($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$this->success('点赞成功', '', array('like_count'=>$news_info['like_count']+1));
    }
    
    public function forbidAction($id = 0){
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	 
    	$news_info = $this->newsService()->getInfo($id);
    	if (empty($news_info)) {
    		$this->error('文章不存在');
    	}
    	 
    	$map = array(
    			'news_id'=>$news_info['news_id'],
    			'user_id'=>$this->user['user_id'],
    	);
    	$like_info = $this->newsForbidService()->findInfo($map);
    	if (!empty($like_info)) {
    		$this->error('您已经禁止过');
    	}
    	 
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'news_id'=>$news_info['news_id'],
    	);
    	try {
    		$result = $this->newsForbidService()->infoCreateOrModify($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	 
    	$this->success('禁止成功');
    }
    
    public function commentAction($news_id = 0){
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$news_info = $this->newsService()->getInfo($news_id);
    	if (empty($news_info)) {
    		$this->error('文章不存在');
    	}
    	
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	try {
    		$result = $this->newsCommentService()->infoCreateOrModify($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$result = $this->newsCommentService()->infoPagerList($result['comment_id']);
    	$this->assign('comment_list', $result['list']);
    	$data = array(
    			'html'=>$this->renderPartial('_comment_list'),
    			'comment_count'=>$news_info['comment_count'] + 1,
    	);
    	$this->success('评论成功', '', $data);
    }
	
    public function comment_likeAction($id = 0){
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$comment_info = $this->newsCommentService()->getInfo($id);
    	if (empty($comment_info)) {
    		$this->error('评论不存在');
    	}
    	
    	$map = array(
    			'comment_id'=>$comment_info['comment_id'],
    			'user_id'=>$this->user['user_id'],
    	);
    	$like_info = $this->newsCommentLikeService()->findInfo($map);
    	if (!empty($like_info)) {
    		$this->error('您已经点赞过');
    	}
    	
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'comment_id'=>$comment_info['comment_id'],
    	);
    	try {
    		$result = $this->newsCommentLikeService()->infoCreateOrModify($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$this->success('点赞成功', '', array('like_count'=>$comment_info['like_count']+1));
    }
    
    public function collectAction($id = 0){
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	//收藏
    	$params = array(
    			'news_id'=>$id,
    			'user_id'=>$this->user['user_id'],
    	);
    	try {
    		$result = $this->newsService()->collect($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$this->success('操作成功');
    }
    
    public function searchAction($keyword = ''){
    	$this->assign('keyword', $keyword);
    	
    	//热门关键词
    	$keyword_list = $this->newsKeywordService()->infoAllList();
    	$this->assign('keyword_list', $keyword_list);
    	
    	//新闻列表
    	if ($keyword) {
    		$params = array('keyword'=>$keyword);
    		$result = $this->newsService()->infoPagerList($params);
    		$this->assign('count', $result['count']);
    		
    		$list = array();
    		foreach ($result['list'] as $v) {
    			$v['title'] = str_replace($keyword, '<font color="red">'.$keyword.'</font>', $v['title']);
    			$list[] = $v;
    		}
    		$this->assign('list', $list);
    	}
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_search');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$this->assign('page_title', '搜索');
    	$this->display();
    }
    
    public function set_tipsAction($id = 0) {
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$map = array(
    			'cat_id'=>$id,
    			'type'=>0,
    	);
    	$cat_info = $this->newsService()->getCatInfo($map);
    	if (empty($cat_info)) {
    		$this->error('分类不存在');
    	}
    	
    	if (empty($this->user['news_cat_ids'])) {
    		$news_cat_ids = $id;
    	}else {
    		$news_cat_ids = explode(',', $this->user['news_cat_ids']);
    		if (!in_array($id, $news_cat_ids)) {
    			$news_cat_ids[] = $id;
    		}
    		$news_cat_ids = implode(',', $news_cat_ids);
    	}
    	$data = array('news_cat_ids'=>$news_cat_ids);
    	try {
    		$result = $this->userService()->modify($this->user, $data);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('操作成功');
    }
    
    public function del_tipsAction($id = 0) {
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	 
    	if (!empty($this->user['news_cat_ids'])) {
    		$news_cat_ids = explode(',', $this->user['news_cat_ids']);
    		foreach ($news_cat_ids as $k => $v) {
    			if ($v == $id) {
    				unset($news_cat_ids[$k]);
    			}
    		}
    		$news_cat_ids = implode(',', $news_cat_ids);
    	}
    	$data = array('news_cat_ids'=>$news_cat_ids);
    	try {
    		$result = $this->userService()->modify($this->user, $data);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('操作成功');
    }
    
    //打赏
    public function rewardAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$map = array(
    			'news_id'=>$id,
    			'is_open'=>1,
    	);
    	$news_info = $this->newsService()->findInfo($map);
    	if(empty($news_info)) $this->error('新闻不存在');
    	$author = $this->newsAuthorService()->getInfo($news_info['author_id']);
    	if(empty($author)) $this->error('作者不存在');
    	
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
    		$post['news_id'] = $news_info['news_id'];
    		$post['author_id'] = $news_info['author_id'];
    		try {
    			$reward_id = $this->newsRewardService()->createReward($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		
    		$post['reward_id'] = $reward_id;
    		if($post['pay_id'] == 1) { //余额支付
    			try {
    				$result = $this->newsRewardService()->payReward($post);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    			$this->success('打赏成功', U('success', array('id'=>$reward_id)));
    		} elseif ($post['pay_id'] == 2) { //微信支付
    			$wxconf = $this->configService()->findWeixinConfigs();
    			$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
    			//$unifiedOrder->setParameter("openid",$this->user['openid']);//openid
    			$unifiedOrder->setParameter("openid",cookie('openid_ck8'));
    			$unifiedOrder->setParameter("body","打赏文章");//商品描述
    			$unifiedOrder->setParameter("out_trade_no",$reward_id);//订单号
    			$unifiedOrder->setParameter("total_fee",($post['reward_amount']*100));//总金额
    			$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/weixin/reward.php');//通知地址
    			$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
    			//var_dump($unifiedOrder);
    			$jsApiParameters = $unifiedOrder->createTradeDataForJS();
    			//var_dump($jsApiParameters);exit;
    			$this->assign("reward_id",$reward_id);
    			$this->assign("jsApiParameters",$jsApiParameters);
    			$this->display('weixinpay');
    		} elseif ($post['pay_id'] == 3) { //支付宝支付
    
    		}
    	}
    	
    	$this->assign('info', $news_info);
    	$this->assign('author', $author);
    	
    	$this->display();
    }
    
    public function successAction($id = 0) {
    	layout(false);
    	$params = array(
    			'reward_id'=>$id,
    			'user_id'=>$this->user['user_id']
    	);
    	$info = $this->newsRewardService()->rewardInfo($params);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	$this->display();
    }
    
	private function newsService(){
    	return D('Information\News', 'Service');
    }
    
    private function newsLikeService(){
    	return D('Information\NewsLike', 'Service');
    }
    
    private function newsCommentService(){
    	return D('Information\NewsComment', 'Service');
    }
    
    private function newsCommentLikeService(){
    	return D('Information\NewsCommentLike', 'Service');
    }
    
    private function newsKeywordService(){
    	return D('Information\NewsKeyword', 'Service');
    }
    
    private function newsCollectService(){
    	return D('Information\NewsCollect', 'Service');
    }
    
    private function newsRewardService(){
    	return D('Information\NewsReward', 'Service');
    }
    
    private function newsAuthorService(){
    	return D('Information\NewsAuthor', 'Service');
    }
    
    private function newsForbidService(){
    	return D('Information\NewsForbid', 'Service');
    }
}