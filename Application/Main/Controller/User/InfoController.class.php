<?php
namespace Main\Controller\User;
use Main\Controller\MainController;

class InfoController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		if (IS_POST) {
			$post = I('post.');
			extract($post);
			
			if (empty($nick_name)) {
				$this->error('昵称不能为空');
			}else {
				$map = array(
						'user_id'=>array('neq', session('userid')),
						'nick_name'=>$nick_name,
				);
				$user_info = $this->userService()->getUser($map);
				if ($user_info) $this->error('昵称已存在');
			}
			
			$data = array();
			isset($sex) && $data['sex'] = $sex;
			isset($nick_name) && $data['nick_name'] = $nick_name;
			!empty($year) && !empty($month) && !empty($day) && empty($this->user['birthday'][0]) && $data['birthday'] = $year.'/'.$month.'/'.$day;
			//isset($mobile) && $data['mobile'] = $mobile;
			isset($email) && $data['email'] = $email;
			isset($real_name) && $data['real_name'] = $real_name;
			/* if($_FILES['user_img']['name']){
			    $upload = new \Think\Upload(); // 实例化上传类
			    $upload->maxSize   =     31457280 ;// 设置附件上传大小
			    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',); // 设置附件上传类型
			    $upload->rootPath  =     UPLOAD_PATH; //'./Uploads/'; // 设置附件上传根目录
			    $upload->savePath  =     'editor/'; // 设置附件上传（子）目录
				$upload->subName   =     array('date', 'Ym'); 
				$info = $upload->uploadOne($_FILES['user_img']);
				if(!$info) $this->error($upload->getError());
				$data['user_img'] = $info['savepath'].$info['savename'];
			} */
			$images = createBase64Image($post['user_img']);
			if (!empty($images)) {
				$data['user_img'] = $images[0];
			}
			if(empty($data) || $this->userService()->modify($this->user, $data) === false){
				$this->error('操作失败');
			}
			$this->success('资料修改成功', U('user/index/index'));
		}
		
		$this->display();
    }
}