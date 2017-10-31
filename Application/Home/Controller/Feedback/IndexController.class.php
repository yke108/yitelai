<?php
namespace Home\Controller\Feedback;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*客服管理*/
    	//管理员
    	$admin_info = $this->adminService()->getAdmin(session('uid'));
    	$this->assign('admin', $admin_info);
    	
    	//是否店铺
    	if ($admin_info['sys_id'] == Status::SysIdDistributor) {
    		$distributor_info = $this->distributorService()->getInfo($admin_info['org_id']);
    		$this->assign('distributor', $distributor_info);
    	}
    	
    	//解答统计
    	$params = array(
    			'distributor_id'=>session('distributor_id'),
    	);
    	$feedback_stat = $this->feedbackService()->stat($params);
    	$this->assign('feedback_stat', $feedback_stat);
    	
    	//所有客服
    	$map = array(
    			'sys_id'=>session('sys_id'),
    			'admin_type'=>array('in', array(Status::AdminTypePreSale, Status::AdminTypeAfterSale, Status::AdminTypeVisit, Status::AdminTypeComplaint)),
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$map['org_id'] = session('distributor_id');
    	}
    	$admin_count = $this->adminService()->adminCount($map);
    	$this->assign('admin_count', $admin_count);
    	
    	$this->assign('page_title', '客服管理');
    	$this->display();
    }
    
    public function listAction() { /*NoPurview*/
    	if (IS_AJAX) {
    		$params = array(
    				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    				'pagesize'=>$this->pagesize,
    				'distributor_id'=>session('distributor_id'),
    				'status'=>I('status'),
    		);
    		if (I('keyword')) {
    			$params['keyword'] = I('keyword');
    		}
    		if (I('start_time')) {
    			$params['start_time'] = I('start_time');
    		}
    		if (I('end_time')) {
    			$params['end_time'] = I('end_time');
    		}
    		if (I('user_id')) {
    			$params['user_id'] = I('user_id');
    		}
    		if (I('from')) {
    			$params['from'] = I('from');
    		}
    		if (I('admin_id')) {
    			$params['admin_id'] = I('admin_id');
    		}
    		$result = $this->feedbackService()->getPagerList($params);
    		$this->assign('list', $result['list']);
    	
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//客服筛选
    	$map = array(
    			'distributor_id'=>session('distributor_id'),
    			'admin_type'=>array('in', array(Status::AdminTypePreSale, Status::AdminTypeAfterSale, Status::AdminTypeVisit, Status::AdminTypeComplaint)),
    	);
    	$admin_list = $this->adminService()->adminAllList($map);
    	$this->assign('admin_list', $admin_list);
    	
    	//解答统计
    	$params = array(
    			'distributor_id'=>session('distributor_id'),
    	);
    	$feedback_stat = $this->feedbackService()->stat($params);
    	$this->assign('feedback_stat', $feedback_stat);
    	
    	//未解答
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>session('distributor_id'),
    			'status'=>Status::FeedbackStatusNone,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	if (I('client')) {
    		$params['client'] = I('client');
    	}
    	if (I('admin_id')) {
    		$params['admin_id'] = I('admin_id');
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('none_list', $result['list']);
    	
    	//解答中
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>session('distributor_id'),
    			'status'=>Status::FeedbackStatusOn,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	if (I('client')) {
    		$params['client'] = I('client');
    	}
    	if (I('admin_id')) {
    		$params['admin_id'] = I('admin_id');
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('on_list', $result['list']);
    	
    	//已解答
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>session('distributor_id'),
    			'status'=>Status::FeedbackStatusDone,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	if (I('client')) {
    		$params['client'] = I('client');
    	}
    	if (I('admin_id')) {
    		$params['admin_id'] = I('admin_id');
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('done_list', $result['list']);
    	
    	$this->display();
    }
    
    public function infoAction($log_id = 0) { /*NoPurview*/
    	$info = $this->feedbackService()->getInfo($log_id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	if (IS_POST) {
    		$post = I('post.');
    		$post['ref_id'] = session('uid');
    		$post['ref_type'] = Status::FeedbackRefTypeAdmin;
    		try {
    			$result = $this->feedbackService()->reply($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		
    		//发送客服消息
    		$user_info = $this->userService()->getUserInfo($result['user_id']);
    		$data = array(
    				'touser' => $user_info['openid'],
    				'msgtype' => 'text',
    				'text' => array('content'=>urlencode($post['content']))
    		);
    		$config = $this->configService()->findWeixinConfigs();
    		$access_token = $this->weixinService()->getAccessToken($config['js_app_id'], $config['js_app_secret']);
    		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$access_token;
    		$json_data = json_encode($data);
    		$rs = curlPost($url, urldecode($json_data));
    		$ret = json_decode($rs, true);
    		
    		$this->success('提交成功');
    	}
    	
    	session('feedback_log_id', $info['log_id']);
    	
    	$this->assign('info', $info);
    	
    	//店铺商品
    	if ($info['goods_id'] > 0) {
    		$distributor_goods = $this->distributorGoodsService()->getInfo($info['goods_id']);
    		$this->assign('goods', $distributor_goods);
    	}
    	
    	//回复列表
    	$params = array(
    			'log_id'=>$info['log_id'],
    	);
    	$reply_list = $this->feedbackService()->replyAllList($params);
    	if ($reply_list) {
    		foreach ($reply_list as $k => $v) {
    			$content = ($v['is_json'] == 1) ? json_decode($v['content'],true) : $v['content'];
    			$reply_list[$k]['content'] = ($v['is_json'] == 1) ? $content['qlist']['content'] : $content;
    			
    			$reply_ids[] = $v['reply_id'];
    		}
    		$this->assign('reply_list', $reply_list);
    		
    		//设为已读
    		$map = array('reply_id'=>array('in', $reply_ids));
    		$data = array('is_read'=>1);
    		try {
    			$this->feedbackService()->feedbackReplyUpdate($map, $data);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	//管理员
    	$admin_info = $this->adminService()->getAdmin(session('uid'));
    	$admin_info['avatar'] = $admin_info['avatar'] ? picurl($admin_info['avatar'], 'b90') : '';
    	$this->assign('admin', $admin_info);
    	
    	$this->assign('page_title', '解答详情');
    	$this->display();
    }
    
    public function closeAction($log_id = 0) { /*NoPurview*/
    	$info = $this->feedbackService()->getInfo($log_id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	$post = I('post.');
    	$data = array('status'=>Status::FeedbackStatusDone);
    	try {
    		$this->feedbackService()->modify($log_id, $data);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('操作成功');
    }
    
    /**
     * 客服回复查询
     */
    public function ssayAction(){
    	$info = $this->feedbackService()->getInfo(session('feedback_log_id'));
    	if ($info['status'] == Status::FeedbackStatusDone){
    		session('manual_flag', null);
    		$this->error('end');
    	}
    	$info = $this->feedbackService()->getUserNextReply(session('feedback_log_id'));
    	if (empty($info)){
    		$this->error('w');
    	}
    	$this->assign('info', $info);
    	$this->display();
    }
    
    private function feedbackService(){
    	return D('Feedback','Service');
    }
    
    protected function weixinService(){
    	return D('Weixin', 'Service');
    }
    
    private function distributorGoodsService(){
    	return D('Distributor\Goods', 'Service');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
}