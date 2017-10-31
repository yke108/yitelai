<?php
namespace News\Controller\Video;
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
    	
    	//推荐视频
    	$params = array('is_open'=>1, 'is_recommend'=>1, 'type'=>1, 'map'=>$map_forbid);
    	$recommend_list = $this->newsService()->infoPagerList($params);
    	$this->assign('recommend_list', $recommend_list['list']);
    	
    	//视频分类
    	$map = array('parent_id'=>0, 'type'=>1);
    	if (!empty($this->user['video_cat_ids'])) {
    		$map['cat_id'] = array('in', $this->user['video_cat_ids']);
    	}
    	$cat_list = $this->newsService()->catAllList($map);
    	foreach ($cat_list as $k => $v) {
    		$params = array('is_open'=>1, 'cat_id'=>$v['cat_id'], 'type'=>1, 'map'=>$map_forbid);
    		$news_list = $this->newsService()->infoPagerList($params);
    		$cat_list[$k]['news_list'] = $news_list['list'];
    	}
    	$this->assign('cat_list', $cat_list);
    	
    	//全部分类
    	$map = array(
    			'parent_id'=>0,
    			'type'=>1,
    	);
    	$cat_all_list = $this->newsService()->catAllList($map);
    	$this->assign('cat_all_list', $cat_all_list);
    	
    	$this->assign('page_title', '视频');
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
    	$map_forbid = array('news_id'=>array('not in', $forbid_news_ids));
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'is_open'=>1,
    			'type'=>1,
    			'map'=>$map_forbid,
    	);
    	if (intval(I('is_recommend')) > 0) {
    		$params['is_recommend'] = intval(I('is_recommend'));
    	}
    	if (intval(I('cat_id')) > 0) {
    		$params['cat_id'] = intval(I('cat_id'));
    	}
    	$result = $this->newsService()->infoPagerList($params);
    	
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
    			'type'=>1,
    	);
    	$info = $this->newsService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('视频不存在');
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
    	
    	//相关视频
    	$params = array(
    			'pagesize'=>3,
    			'cat_id'=>$info['cat_id'],
    			'map'=>array('news_id'=>array('neq', $info['news_id'])),
    			'is_open'=>1,
    			'type'=>1,
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
    	
    	$this->assign('page_title', '视频详情');
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
    
    public function set_tipsAction($id = 0) {
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$map = array(
    			'cat_id'=>$id,
    			'type'=>1,
    	);
    	$cat_info = $this->newsService()->getCatInfo($map);
    	if (empty($cat_info)) {
    		$this->error('分类不存在');
    	}
    	
    	if (empty($this->user['video_cat_ids'])) {
    		$video_cat_ids = $id;
    	}else {
    		$video_cat_ids = explode(',', $this->user['video_cat_ids']);
    		if (!in_array($id, $video_cat_ids)) {
    			$video_cat_ids[] = $id;
    		}
    		$video_cat_ids = implode(',', $video_cat_ids);
    	}
    	$data = array('video_cat_ids'=>$video_cat_ids);
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
    	
    	if (!empty($this->user['video_cat_ids'])) {
    		$video_cat_ids = explode(',', $this->user['video_cat_ids']);
    		foreach ($video_cat_ids as $k => $v) {
    			if ($v == $id) {
    				unset($video_cat_ids[$k]);
    			}
    		}
    		$video_cat_ids = implode(',', $video_cat_ids);
    	}
    	$data = array('video_cat_ids'=>$video_cat_ids);
    	try {
    		$result = $this->userService()->modify($this->user, $data);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('操作成功');
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
    
    private function newsForbidService(){
    	return D('Information\NewsForbid', 'Service');
    }
}