<?php
namespace Distributor\Controller\Weixin;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class WxkefuController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'weixin',
			'ac'=>'weixin_wxkefu_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
		$this->display();
    }
	
	//文本消息
	public function textAction(){
		if(IS_POST){
			$post = I('post.');
			
			$text = M('wx_text')->find($post['text_id']);
			$data = array(
					//'touser' => 'oh842t4CMxE_65VKPfJ3V6PfprCs',
					'msgtype' => 'text',
					'text' => array('content'=>urlencode($text['content']))
			);
			
			$res = $this->send($data);
			
			if (!$res) {
				$this->error('发送失败');
			}
			$this->success('发送成功');
		}
		$map = array(
				'distributor_id'=>$this->org_id
		);
		$count = M('wx_text')->where($map)->count();
		if ($count) {
			$page = intval(I('p')) ? intval(I('p')) : 1;
			$list = M('wx_text')->where($map)->order('text_id DESC')->page($page, $this->pagesize)->select();
			$this->assign('list', $list);
			$pager = new Pager($count, $this->pagesize);
			$this->assign('pager', $pager->show());
		}
		
		$this->display();
    }
    
    public function textAddAction(){
    	if(IS_POST){
    		$post = I('post.');
    		$data = array(
    				'distributor_id'=>$this->org_id,
    				'content'=>trim($post['content']),
    				'add_time'=>NOW_TIME
    		);
    		$rules = array(
    				array('content', 'require', '内容不能为空'),
    		);
    		if (!M('wx_text')->validate($rules)->create($data)) {
    			$this->error(M('wx_text')->getError());
    		}
    		$res = M('wx_text')->add();
    		if (!$res) {
    			$this->error('保存失败');
    		}
    		$this->success('保存成功', U('text'), 0);
    	}
    	$this->display('textEdit');
    }
    
    public function textEditAction($text_id = 0){
    	$map = array(
    			'distributor_id'=>$this->org_id,
    			'text_id'=>$text_id
    	);
    	$info = M('wx_text')->where($map)->find();
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	if(IS_POST){
    		$post = I('post.');
    		$data = array(
    				'distributor_id'=>$this->org_id,
    				'text_id'=>$text_id,
    				'content'=>trim($post['content'])
    		);
    		$rules = array(
    				array('content', 'require', '内容不能为空'),
    		);
    		if (!M('wx_text')->validate($rules)->create($data)) {
    			$this->error(M('wx_text')->getError());
    		}
    		$res = M('wx_text')->save();
    		if (!$res) {
    			$this->error('保存失败');
    		}
    		$this->success('保存成功', U('text'), 0);
    	}
    	$this->display();
    }
	
	//图片消息
	public function imageAction(){
		if(IS_POST){
			$post = I('post.');
			
			$media = M('wx_media')->find($post['id']);
			$media_id = $media['media_id'];
			$data = array(
					//'touser' => 'oh842t4CMxE_65VKPfJ3V6PfprCs',
					'msgtype' => 'image',
					'image' => array('media_id'=>$media_id)
			);
			
			$res = $this->send($data);
			
			if (!$res) {
				$this->error('发送失败');
			}
			$this->success('发送成功');
		}
		
		$map = array(
			'type'=>'image'
		);
		$count = M('wx_media')->where($map)->count();
		if ($count) {
			$page = intval(I('p')) ? intval(I('p')) : 1; $pagesize = 20;
			$list = M('wx_media')->where($map)->page($page.','.$pagesize)->order('id desc')->select();
			$this->assign('list', $list);
			$pager = new Pager($count, $pagesize);
			$this->assign('pager', $pager->show());
		}
		
		$this->display();
	}
	
	//图片上传
	public function imageAddAction(){
		if(IS_POST){
			$post = I('post.');
			if(empty($post['picture'])){
				$this->error('图片不能为空');
			}
			$res = $this->uploadImage($post['picture']);
			if ($res['Error']) {
				$this->error($res['Message']);
			}
			$this->success($res['Message'], U('image'));
		}
		
		$this->display();
	}
	
	//新增其他类型永久素材
	private function uploadImage($picture) {
		$wx_configs = $this->configService()->findConfigs('weixin', $this->org_id);
		$token = getTokendb($this->org_id, $wx_configs);
		$media_type = "image"; //设置上传媒体文件类型
		//$url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$token;
		//$url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."&type=".$media_type;
		$url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=$token&type=$media_type";
		$post_data = array(
				//"type" => $media_type,
				"media" => "@".UPLOAD_PATH.$picture
		);
		$ret = https_request($url, $post_data);
		if($ret['errcode']){
			return array('Error'=>1,'Message'=>$ret['errmsg']);
		}else{
			$data = array(
					'type'=>$media_type,
					'media_id'=>$ret['media_id'],
					'media_name'=>$picture,
					'add_time'=>NOW_TIME,
			);
			$res = M('wx_media')->add($data);
			if (!$res) {
				return array('Error'=>1,'Message'=>'添加失败');
			}
			return array('Error'=>1,'Message'=>'添加成功');
		}
	}
	
	//语音列表
	public function voiceAction(){
		if(IS_POST){
			$post = I('post.');
				
			$media = M('wx_media')->find($post['id']);
			$media_id = $media['media_id'];
			$data = array(
					//'touser' => 'oh842t4CMxE_65VKPfJ3V6PfprCs',
					'msgtype' => 'voice',
					'voice' => array('media_id'=>$media_id)
			);
				
			$res = $this->send($data);
				
			if (!$res) {
				$this->error('发送失败');
			}
			$this->success('发送成功');
		}
		
		$map = array(
				'type'=>'voice'
		);
		$count = M('wx_media')->where($map)->count();
		if ($count) {
			$page = intval(I('p')) ? intval(I('p')) : 1;$pagesize = 20;
			$list = M('wx_media')->where($map)->page($page.','.$pagesize)->order('id desc')->select();
			$this->assign('list', $list);
			$pager = new Pager($count, $pagesize);
			$this->assign('pager', $pager->show());
		}
		
		$this->display();
	}
	
	//添加语音素材
	public function voiceAddAction(){
		if(IS_POST){
			$file_voice = $_FILES['file_voice'];
			if(empty($file_voice)){
				$this->error('文件不能为空');
			}
			
			$upload = new \Think\Upload(); // 实例化上传类
			$upload->maxSize   =     31457280 ;// 设置附件上传大小
			$upload->exts      =     array('mp3','amr');// 设置附件上传类型
			$upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
			$upload->savePath  =     'media/'; // 设置附件上传（子）目录
			$upload->subName   =      array('date', 'Ym'); 
			$info = $upload->uploadOne($_FILES['file_voice']);
			if($info) {
				$file_voice = UPLOAD_PATH.$info['savepath'].$info['savename'];
			}
			
			$res = $this->uploadVoice($file_voice);
			if ($res['Error']) {
				$this->error($res['Message']);
			}
			$this->success($res['Message'], U('voice'));
		}
		
		$this->display();
	}
	
	private function uploadVoice($file_voice) {
		$wx_configs = $this->configService()->findConfigs('weixin', $this->org_id);
		$token = getTokendb($this->org_id, $wx_configs);
		$media_type ="voice"; //设置上传image媒体文件类型
		//$url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$token;
		$url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."&type=".$media_type;
		$post_data = array(
				"type" => $media_type,
				"media" => "@".$file_voice
		);
		$ret = curl_file($url, $post_data);
		if($ret['errcode']){
			return array('Error'=>1, 'Message'=>$ret['errmsg']);
		}else{
			$data = array(
					'type'=>$media_type,
					'media_id'=>$ret['media_id'],
					'media_name'=>$file_voice,
					'add_time'=>NOW_TIME,
			);
			$res = M('wx_media')->add($data);
			if (!$res) {
				return array('Error'=>1, 'Message'=>'保存失败');
			}
			return array('Error'=>0, 'Message'=>'保存成功');
		}
	}
	
	//图文列表
	public function newsAction(){
		if(IS_POST){
			$post = I('post.');
			
			$info = M('wx_news')->find($post['id']);
			$qian=array(" ","　","\t","\n","\r");
			$articles[] = array(
					'title'=>urlencode($info['title']),
					//'description'=>mb_substr(str_replace($qian, '', $info['description']), 0, 100, 'utf-8'),
					'description'=>urlencode($info['description']),
					'url'=>urlencode($info['url']),
					'picurl'=>urlencode(picurl($info['picture'])),
			);
			$data = array(
					//'touser' => 'oh842t4CMxE_65VKPfJ3V6PfprCs',
					'msgtype' => 'news',
					'news' => array('articles'=>$articles)
			);
			$res = $this->send($data);
			
			if (!$res) {
				$this->error('发送失败');
			}
			$this->success('发送成功');
		}
		
		$map = array('distributor_id'=>$this->org_id);
		$count = M('wx_news')->where($map)->count();
		if ($count) {
			$page = intval(I('p')) ? intval(I('p')) : 1;$pagesize = $this->pagesize;
			$list = M('wx_news')->page($page, $pagesize)->order('id DESC')->select();
			$this->assign('list', $list);
			$pager = new Pager($count, $pagesize);
			$this->assign('pager', $pager->show());
		}
		
		$this->display();
	}
	
	//添加图文
	public function newsAddAction(){
		if(IS_POST){
			$post = I('post.');
			$data = array(
					'distributor_id' =>$this->org_id,
					'title'=>trim($post['title']),
					'description'=>trim($post['description']),
					'picture'=>trim($post['picture']),
					'url'=>$post['url'],
					'sort_order'=>$post['sort_order'],
					'add_time'=>NOW_TIME,
					'add_ip'=>get_client_ip(),
			);
			$id = D("wx_news")->add($data);
			if(!$id){
				$this->error('添加失败');
			}
			$this->success('添加成功', U('news'));
		}
		
		$this->display('newsEdit');
	}
	
	//编辑图文
	public function newsEditAction($id = 0){
		$map = array(
				'id'=>$id,
				'distributor_id'=>$this->org_id
		);
		$info = D('wx_news')->where($map)->find();
		if(empty($info)){
			$this->error('文章不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			$data = array(
					'title'=>trim($post['title']),
					'picture'=>$post['picture'],
					'description'=>$post['description'],
					'url'=>$post['url'],
					'sort_order'=>$post['sort_order'],
			);
			$res = D('wx_news')->where($map)->save($data);
			if(!$res){
				$this->error('修改失败');
			}
			$this->success('修改成功', U('news'));
		}
		
		$this->display();
	}
	
	//删除图文
	public function newsDelAction($id) {
		$map = array(
				'id'=>$id,
				'distributor_id'=>$this->org_id,
		);
		$res = D('wx_news')->where($map)->delete();
		if (!$res) {
			$this->error('删除失败');
		}
		$this->success('删除成功');
	}
	
	private function send($data){
		$wx_configs = $this->configService()->findConfigs('weixin', $this->org_id);
		$token = getTokendb($this->org_id, $wx_configs);
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$token;
		
		$data_wx_log = array(
				'distributor_id'=>$this->org_id,
				'msg_type'=>$data['msgtype'],
				'start_time'=>time()
		);
		$log_id = M('wx_log')->add($data_wx_log);
		
		$success_count = 0;$fail_count = 0;
		$users = $this->getUsers();
		foreach ($users as $user) {
			$data['touser'] = $user['openid'];
			$json_data = json_encode($data);
			$rs = curlPost($url, urldecode($json_data));
			$ret = json_decode($rs, true);
			
			if ($ret['errcode']) {
				$fail_count++;
			}else {
				$success_count++;
			}
			
			$res = $this->send_log($ret, $user);
			if (!res) {
				return false;
			}
		}
		
		return $this->update_log($log_id, $success_count, $fail_count);
	}
	
	private function getUsers(){
		$map = array(
				'openid'=>array('neq', '')
		);
		return M('user_info')->where($map)->select();
	}
	
	private function send_log($ret, $user, $log_id){
		$data = array(
				'log_id'=>$log_id,
				'user_id'=>$user['user_id'],
				'add_time'=>time()
		);
		if ($ret['errcode']) {
			$data['status'] = 0;
		}else {
			$data['status'] = 1;
		}
		return M('wx_send_log')->add($data);
	}
	
	private function log($ret, $user){
		
	}
	
	private function update_log($log_id, $success_count, $fail_count){
		$data = array(
				'success_count'=>$success_count,
				'fail_count'=>$fail_count,
				'end_time'=>time()
		);
		return M('wx_log')->where(array('log_id'=>$log_id))->save($data);
	}
}