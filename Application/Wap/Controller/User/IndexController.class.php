<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class IndexController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		//未读消息
    	$map = array(
    			'type'=>0,
    			'add_time'=>array('gt',$this->user['reg_time'])
		);
    	$message_status = M('message_status')->where(array('user_id'=>$this->user['user_id']))->getField('id, msg_id');
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
    	$system_count = $this->messageService()->searchMessagesCount($map);
		
    	$map = array(
    		'user_id'=>$this->user['user_id'],
			'type'=>array('gt',0),
			//'msg_id' => array('not in', $message_status),
    	);
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
		
    	$user_count = $this->messageService()->searchMessagesCount($map);
		//var_dump($system_count);die();
    	$noread=$user_count+$system_count;
		$this->assign('noread', $noread);
		
		//订单统计
		$params = array(
				'user_id'=>$this->user['user_id']
		);
		$uon = $this->orderService()->getOrderCount($params);
		$this->assign('uon', $uon);
		
		$this->display();
    }
	
	public function infoAction(){
		if (IS_POST) {
			$post = I('post.');
			extract($post);
		
			$data = array();
			isset($sex) && $data['sex'] = $sex;
			isset($nick_name) && $data['nick_name'] = $nick_name;
			isset($birthday) && $data['birthday'] = $birthday;
			if($_FILES['user_img']['name']){
			    $upload = new \Think\Upload(); // 实例化上传类
			    $upload->maxSize   =     31457280 ;// 设置附件上传大小
			    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',); // 设置附件上传类型
			    $upload->rootPath  =     UPLOAD_PATH; //'./Uploads/'; // 设置附件上传根目录
			    $upload->savePath  =     $savepath; // 设置附件上传（子）目录
				$upload->subName   =     array('date', 'Ym'); 
				$info = $upload->uploadOne($_FILES['user_img']);
				if(!$info) $this->error($upload->getError());
				$data['user_img'] = $info['savepath'].$info['savename'];
			}

			if(empty($data) || $this->userService()->modify($this->user, $data) === false){
				$this->error('操作失败');
			}
			$this->success('资料修改成功', U('user/index'));
		}
		$this->display();
	}
	public function testAction(){
		$curl = curl_init();
		//$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential';
		//$url .= '&appid='.$appid;
		//$url .= '&secret='.$secret;
		$url = 'http://api.ygxxcn.com/v1/wechat/access-token';
        $header = "Jy-Secret: C9vke78wuUHVci"; 
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($header));
		$data = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($data, true);
        if(!$result->errCode) {
            print_r($result->data);
        }
        print_r($result);
	}
	
	//申请分销员
	public function apply_salemanAction(){
		
		if(IS_POST){
			try{
				$this->userService()->applySaleman($this->user['user_id']);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('提交申请成功');
			die();
		}
		
		//var_dump($this->user);die();
		if($this->user['user_type']!=2){
		//	$this->redirect('user/index/index');
		}
		$this->assign('info',$this->user);
		$apply_info=$this->salemanService()->userGetInfo($this->user['user_id']);
		$this->assign('apply_info',$apply_info);
		$this->display();
	}
	
	
	
	private function orderService() {
		return D('Order', 'Service');
	}
	
	private function messageService() {
		return D('Message', 'Service');
	}
	
	private function salemanService(){
		return D('Saleman','Service');
	}
}
