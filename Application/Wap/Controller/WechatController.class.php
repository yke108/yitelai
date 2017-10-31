<?php
namespace Wap\Controller;
use Common\Basic\Status;

class WechatController extends WapController {
	public $token;
	private $from;
	private $content;
	private $ekey;
	
	public function _initialize(){
		
    }
	
	public function indexAction(){
		header("Content-type:text/html;charset=utf-8");
		
		$wx_configs = $this->configService()->findWeixinConfigs();
		
		$weixin = new \Common\WeixinPay\Wechat($wx_configs['token']);
		//M('test')->add(array('txt'=>json_encode($data)));
		
		$data = $weixin->request ();
		//M('test')->add(array('txt'=>json_encode($data)));
		
		$result = $this->reply ($data);
		if (!is_array($result)) return;
		list ( $content, $type ) = $result;
		$weixin->response ( $content, $type );
	}
	
	private function reply($data){
		$this->from = $data['FromUserName'];
		$this->content = $data['Content'];
		$this->ekey = $data['EventKey'];
		$msgtype = $data['MsgType'];
		$event = $data['Event'];
		if('event' == $msgtype){
			if($event=='subscribe')return $this->subscribe($data); //关注
			if($event=='unsubscribe')return $this->unsubscribe($data); //取消关注
			//if($event=="LOCATION")return $this->location($data); //获取用户位置
			if($event=='CLICK')return $this->clicked(); //点击按钮
			if($event=='SCAN')return $this->qrcodeScan(); //扫描二维码
		} elseif ('location' == $msgtype){
			return $this->location($data); //获取用户位置
		} elseif ('text' == $msgtype){
			//$result = $this->findFixedContent($this->ekey);
			$result = $this->feedback();
			empty($result) && $result = $this->searchContentByKeyword($this->ekey);
			empty($result) && $result = $this->resultEmpty();
			return $result;
		} else {
			return $this->resultEmpty();
		}
	}

	private function subscribe($data){
		//获取微信用户信息
		$userInfo = $this->weixinService()->getWeixinUserInfo($data['FromUserName']);
		//M('test')->add(array('txt'=>json_encode($data)));
		//自动注册
		if (strpos($data['EventKey'], 'qrscene_') === 0) {
			$userInfo['qrcode_id'] = substr($data['EventKey'], 8);
		}else {
			$userInfo['qrcode_id'] = $data['EventKey'];
		}
		$result = $this->registerAuto($userInfo);
		//自动回复
		$config = $this->configService()->findWeixinSubscribeConfigs();
		$content = $result['again'] ? $config['again'] : $config['first'];
		$content = str_replace('{NickName}', $userInfo['nickname'], $content);
		return array(
			$content,
			'text'
		);
	}
	
	private function registerAuto($info){
		$openid = $info['openid'];
		$user = $this->userService()->getUserInfoWithOpenid($openid);
		if ($user){
			$data = array(
				'subscribe'=>$info['subscribe'], 
				'subscribe_time'=>$info['subscribe_time'],
			);
			//如果二维码未包含上级，取消上级
			if ($user['parent_id'] > 0){
				$info['qrcode_id'] < 1 && $data['parent_id'] = 0;
				$data['rank_money'] = 0;
				$data['rank_times'] = 0;
			}
			$this->userService()->modify($user, $data);
			return array(
				'again'=>1,
			);
		}
		if ($info['scene_id'] > 1000000){ //临时二维码
			$info['parent_id'] = $info['scene_id'] - 1000000;
		} elseif ($info['scene_id'] > 0){ //永久二维码
			$result = $this->qrcodeService()->infoBySceneId($info['scene_id']);
			foreach ($result as $k => $v) {
				$info[$k] = $v;
			}
		}
		$this->userService()->registerByWeixin($info);
		return array(
			'again'=>0,
		);
	}
	
	private function unsubscribe($data){
		$openid = $data['FromUserName'];
		$user = $this->userService()->getUserInfoWithOpenid($openid);
		if ($user) {
			$data = array('subscribe'=>0);
			$this->userService()->modify($user, $data);
		}
		return array(
			'QX',
			'text'
		);
	}
	
	private function location($data){
		$content = '坐标值：'.$data['Location_X'].' '.$data['Location_Y'];
		return array(
				$content,
				'text'
		);
	}
	
	private function clicked(){
		$content = $this->ekey;
		return array(
			$content,
			'text'
		);
	}
	
	private function qrcodeScan(){
		$ekey = $this->ekey;
		return array($this->ekey,'text');
	}
	
	private function feedback(){
		$user = $this->userService()->getUserInfoWithOpenid($this->from);
		if ($user) {
			$post['user_id'] = $user['user_id'];
			$map = array(
					'user_id'=>$user['user_id'],
					'status'=>array('neq', Status::FeedbackStatusDone),
			);
			$feedback_info = $this->feedbackService()->findInfo($map);
		}
		try {
			if (empty($feedback_info)) {
				$post['content'] = $this->content;
				$post['type'] = Status::FeedbackTypeAsk;
				$post['client'] = Status::FeedbackClientWeChat;
				$result = $this->feedbackService()->createOrModify($post);
			}else {
				$post['log_id'] = $feedback_info['log_id'];
				$post['content'] = $this->content;
				$post['ref_id'] = $user['user_id'];
				$post['ref_type'] = Status::FeedbackRefTypeUser;
				$result = $this->feedbackService()->reply($post);
			}
		} catch (\Exception $e) {
			return array (
					$e->getMessage(),
					'text'
			);
		}
		return array (
				'提交成功，请耐心等待管理员回复',
				'text'
		);
	}
	
	private function findFixedContent(){
		return false;
	}
	
	private function searchContentByKeyword(){
		$params = array(
    		'pagesize'=>5,
			'keyword'=>$this->content,
    	);
    	$result = $this->newsService()->infoPagerList($params);
    	foreach($result['list'] as $k => $vo){
    		$url = U('life/index/info', array('id'=>$vo['ArticleId']));
    		$contents[] = array(
    			'Title'=>$vo['title'],
    			'Description'=>$vo['description'],
    			'PicUrl'=>picurl($vo['picture']),
    			'Url'=>DK_DOMAIN.str_replace('wechat.php', 'index.php', $url),
    		);
    	}
		if(empty($contents)) return false;
		return array(
			$contents,'news',
		);
	}
	
	private function resultEmpty(){
		return array (
			'没有找到相关信息',
			'text'
		);
	}
	
	protected function clinicService(){
		return D('Clinic', 'Service');
	}
	
	protected function newsService(){
		return D('News', 'Service');
	}
	
	protected function weixinService(){
		return D('Weixin', 'Service');
	}
	
	protected function qrcodeService(){
		return D('Qrcode', 'Service');
	}
	
	private function feedbackService() {
		return D('Feedback', 'Service');
	}
}