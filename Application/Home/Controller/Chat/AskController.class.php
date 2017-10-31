<?php
namespace Home\Controller\Chat;
use Home\Controller\BaseController;
use Common\Basic\Status;

class AskController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		if (session(manual_flag) == 1 && ACTION_NAME != 'index'){
			$this->ssayAction();
			exit;
		}
    }
    
    public function indexAction(){
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
	
	protected function feedbackService(){
		return D('Feedback', 'Service');
	}
}