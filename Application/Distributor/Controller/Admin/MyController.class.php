<?php
namespace Distributor\Controller\Admin;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class MyController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();
    }
	
    public function profileAction(){
		$gmap = array(
			'admin_id'=>session('uid'),
		);
		$info = M('Admin')->where($gmap)->find();
		
		if(IS_POST){
			$data = array(
				'mobile'=>trim($_POST['phone']),
				'email'=>trim($_POST['email']),
			);
			
		    $upload = new \Think\Upload(); // 实例化上传类
		    $upload->maxSize   =     31457280 ;// 设置附件上传大小
		    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',);// 设置附件上传类型
		    $upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
		    $upload->savePath  =     'avatar/'; // 设置附件上传（子）目录
			$upload->subName   =      array('date', 'Ym'); 
			$info = $upload->uploadOne($_FILES['avatar']);
			if($info) {
				$data['avatar'] = $info['savepath'].$info['savename'];
			}

			$res = M('Admin')->where($gmap)->save($data);
			if($res !== false){
				if(!empty($data['avatar'])){
					$this->sessionSet($data);
				}
				$this->success('修改成功');
			} else {
				$this->error('修改失败');
			}
		}
		
		$this->assign('info', $info);
		$this->display();
    }
	
	public function pwdAction(){
		$uid = session('uid');
		if(IS_POST){
			$password_old = trim($_POST['password_old']);
			$password = trim($_POST['password']);
			$password_re = trim($_POST['password_re']);
			
			if(empty($password_old) || empty($password) || empty($password_re)){
				$this->error('密码不能为空');
			}
			
			if($password != $password_re){
				$this->error('两次输入的新密码不一致');
			}
			
			$gmap = array(
				'admin_id'=>$uid,
			);
			
			$rec = M('Admin')->where($gmap)->find();
			if($rec['password'] != admin_password_md5($password_old, $rec['salt'])){
				$this->error('原密码错误');
			}
		
			//保存新密码
			$salt = rand(1000,9999);
			$data = array(
				'salt' => $salt,
				'password' => admin_password_md5($password, $salt),
			);
			$result = M('Admin')->where($gmap)->save($data);
			if($result === false){
				$this->error('密码修改失败');
			}
			
			$this->success('密码修改成功');
		}
		$this->display();
	}
	
}