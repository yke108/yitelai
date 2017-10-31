<?php
namespace Wap\Controller\Chat;
use Wap\Controller\WapController;
use Common\Basic\Status;

class AskController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		if (session(manual_flag) == 1 && ACTION_NAME != 'index' && ACTION_NAME != 'comment'){
			$this->ssayAction();
			exit;
		}
    }
    
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品
    	if ($get['id']) {
    		$distributor_goods = $this->distributorGoodsService()->getInfo($get['id']);
    		$this->assign('goods', $distributor_goods);
    		
    		$distributor = $this->distributorService()->getInfo($distributor_goods['distributor_id']);
    		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    		$this->assign('distributor', $distributor);
    	}
    	
    	//店铺
    	if ($get['store_id'] > 0) {
    		$distributor = $this->distributorService()->getInfo($get['store_id']);
    		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    		$this->assign('distributor', $distributor);
    	}
    	
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
		$map = [
			'distributor_id'=>$distributor_id,
			'keywords'=>['like', '%'.$post['message'].'%'],
		];
		$list = $this->questionService()->infoList($map);
		
		//保存问题
		if (session('feedback_log_id') < 1){ //记为问题
			$params = [
				'user_id'=>session('userid'),
				'type'=>Status::FeedbackTypeAsk,
				'brand_id'=>$brand_id,
				'distributor_id'=>$distributor_id,
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
	
	//评价
	public function commentAction() {
		$log_id = session('feedback_log_id');
		
		$info = $this->feedbackService()->getInfo($log_id);
		if(empty($info)) $this->error('您已评价过，感谢您的参与');
		
		$post = I('post.');
		if (empty($post['comment'])) $this->error('请输入评价内容');
		$data = array(
				'comment'=>$post['comment'],
				'score'=>$post['score'],
				'end_time'=>NOW_TIME,
				'status'=>Status::FeedbackStatusDone,
		);
		try {
			$this->feedbackService()->modify($log_id, $data);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		session('feedback_log_id', null);
		$this->success('评价成功');
	}
	
	protected function feedbackService(){
		return D('Feedback', 'Service');
	}
	
	protected function questionService(){
		return D('Question', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}