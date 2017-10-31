<?php
namespace Main\Controller\Chat;
use Main\Controller\MainController;
use Common\Basic\Status;

class AskController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		/* if (session(manual_flag) == 1 && ACTION_NAME != 'index'){
			$this->ssayAction();
			exit;
		} */
    }
    
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品页进入
    	if ($get['goods_id']) {
    		//商品
    		$goods = $this->distributorGoodsService()->getInfo($get['goods_id']);
    		if (empty($goods)) $this->error('商品不存在');
    		$this->assign('goods', $goods);
    		
    		//货品列表
    		$params = array('map'=>array('record_id'=>$goods['record_id']));
    		$product_list = $this->distributorGoodsProductService()->getAllList($params);
    		$new_product_list = array();
    		foreach ($product_list as $v) {
    			$new_product_list[$v['product_items']] = $v;
    		}
    		$this->assign('product_list', $new_product_list);
    		$this->assign('product', current($new_product_list));
    		
    		//店铺
    		$distributor = $this->distributorService()->getInfo($goods['distributor_id']);
    		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    		$this->assign('distributor', $distributor);
    		
    		//商品评价
    		$params = array(
    				'goods_id'=>$goods['record_id'],
    				'status'=>1
    		);
    		$datas = $this->goodsCommentService()->getPagerList($params);
    		//累计评价
    		$this->assign('comment_count', $datas['count']);
    	}
    	
    	//店铺页进入
    	if ($get['store_id']) {
    		//店铺
    		$distributor = $this->distributorService()->getInfo($get['store_id']);
    		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    		$this->assign('distributor', $distributor);
    	}
    	
    	//为您推荐
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id'],
    			'pagesize'=>8,
    			'orderby'=>'a.is_hot desc',
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('hot_list', $datas['list']);
    	
    	$this->display();
    }
    
	public function startAction(){
		$post = I('post.');
		$cat_info = $this->questionService()->getCat($post['cat_id']);
		if (empty($cat_info)){
			$this->error('x');
		}
		//获取分类对应的问题列表
		$brand_id = 0;
		$distributor_id = intval($post['distributor_id']);
		$map = [
			'cat_id'=>$post['cat_id'],
			'distributor_id'=>$distributor_id,
		];
		$list = $this->questionService()->infoList($map);
		
		//保存问题
		if (session('feedback_log_id') < 1 || session('manual_flag') != Status::YES){ //记为问题
			$params = [
				'user_id'=>session('userid'),
				'type'=>Status::FeedbackTypeAsk,
				'brand_id'=>$brand_id,
				'distributor_id'=>$distributor_id,
				'content'=>$cat_info['cat_name'],
				'client'=>Status::FeedbackClientPc,
			];
			$result = $this->feedbackService()->createOrModify($params);
			session('feedback_log_id', $result);
		} else { //记为回复
			$params = [
				'log_id'=>session('feedback_log_id'),
				'content'=>$cat_info['cat_name'],
				'ref_id'=>session('userid'),
				'ref_type'=>Status::FeedbackRefTypeUser,
			];
			$this->feedbackService()->reply($params);
		}
		$content = [
			'qlist'=>$list,
		];
		$params = [
			'log_id'=>session('feedback_log_id'),
			'content'=>json_encode($content),
			'is_json'=>Status::YES,
			'ref_id'=>0,
			'ref_type'=>Status::FeedbackRefTypeAdmin,
		];
		$this->feedbackService()->reply($params);
		$this->assign('list', $list);
		$this->display();
	}
	
	public function questionAction(){
		$post = I('post.');
		$brand_id = 0;
		$distributor_id = intval($post['distributor_id']);
		$goods_id = intval($post['goods_id']);
		if ($goods_id > 0) {
			$distributor_goods = $this->distributorGoodsService()->getInfo($goods_id);
			$distributor_id = $distributor_goods['distributor_id'];
		}
		
		//保存问题
		if (session('feedback_log_id') < 1){ //记为问题
			$params = [
				'user_id'=>session('userid'),
				'type'=>Status::FeedbackTypeAsk,
				'brand_id'=>$brand_id,
				'distributor_id'=>$distributor_id,
				'goods_id'=>$goods_id,
				'content'=>$post['message'],
				'client'=>Status::FeedbackClientPc,
			];
			$result = $this->feedbackService()->createOrModify($params);
			session('feedback_log_id', $result);
		} else { //记为回复
			$params = [
				'log_id'=>session('feedback_log_id'),
				'content'=>$post['message'],
				'ref_id'=>session('userid'),
				'ref_type'=>Status::FeedbackRefTypeUser,
			];
			$this->feedbackService()->reply($params);
		}
		
		if (session(manual_flag) == 1 && ACTION_NAME != 'index'){
			$this->ssayAction();
			exit;
		}
		
		$map = [
			'distributor_id'=>$distributor_id,
			'keywords'=>['like', '%'.$post['message'].'%'],
		];
		$list = $this->questionService()->infoList($map);
		
		$content = [
			'qlist'=>$list,
		];
		$params = [
			'log_id'=>session('feedback_log_id'),
			'content'=>json_encode($content),
			'is_json'=>Status::YES,
			'ref_id'=>0,
			'ref_type'=>Status::FeedbackRefTypeAdmin,
		];
		$this->feedbackService()->reply($params);
		
		$this->assign('list', $list);
		$this->assign('message', $post['message']);
		$this->display();
	}
	
	public function detailAction(){
		if (session('feedback_log_id') < 1){
			$this->error('xx');
		}
		$post = I('post.');
		$info = $this->questionService()->getInfo($post['qid']);
		$params = [
			'log_id'=>session('feedback_log_id'),
			'content'=>$info['title'],
			'ref_id'=>session('userid'),
			'ref_type'=>Status::FeedbackRefTypeUser,
		];
		$this->feedbackService()->reply($params);
		$params = [
			'log_id'=>session('feedback_log_id'),
			'content'=>$info['content'],
			'ref_id'=>0,
			'ref_type'=>Status::FeedbackRefTypeAdmin,
		];
		$this->feedbackService()->reply($params);
		$this->assign('info', $info);
		$this->display();
	}
	
	/**
	 * 转人工服务
	 */
	public function mbeginAction(){
		session('manual_flag', 1);
		if (session('feedback_log_id') < 1){
			$this->error('连接超时，请关闭聊天界面重试');
		}
		$params = [
			'log_id'=>session('feedback_log_id'),
		];
		$this->feedbackService()->mbegin($params);
		try {
			$msg = '有新的咨询';
			$tags = ['role58@1', 'role53@738', 'role54@738'];
			$this->jpushService()->notifyCustomerServiceStaff($msg, DK_DOMAIN.'/home/index.php/feedback/index.html', $tags);
		} catch (\Exception $e) {
			
		}
		$this->display();
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
		$info = $this->feedbackService()->getNextReply(session('feedback_log_id'));
		if (empty($info) || $info['ref_id'] < 1){
			$this->error('w');
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function recommend_listAction($id = 0) {
		$map = array(
				'a.product_num'=>array('gt', 0),
				'c.status'=>Status::DistributorStatusNormal,
				'_string'=>'record_id >= (
		(SELECT MAX(record_id) FROM hy_distributor_goods) - (SELECT MIN(record_id) FROM hy_distributor_goods)
	) * RAND() + (SELECT MIN(record_id) FROM hy_distributor_goods)',
		);
		//猜你喜欢
		$params = array(
				'distributor_id'=>$id,
				'pagesize'=>8,
				'map'=>$map,
				'orderby'=>'rand()',
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('hot_list', $datas['list']);
		if(empty($datas['list'])){
			$clist = '';
		}else{
			$clist = $this->renderPartial('recommend_list');
		}
		$this->ajaxReturn($clist);
	}
	
	/**
	 * 关闭对话
	 */
	public function closeAction() {
		$log_id = session('feedback_log_id');
		$data = array(
				'end_time'=>NOW_TIME,
				'status'=>Status::FeedbackStatusDone,
		);
		try {
			$this->feedbackService()->modify($log_id, $data);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		session('feedback_log_id', null);
		$this->success('关闭对话成功');
	}
	
	protected function feedbackService(){
		return D('Feedback', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsProductService(){
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function jpushService(){
		return D('JPush', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function goodsCommentService(){
		return D('GoodsComment','Service');
	}
}