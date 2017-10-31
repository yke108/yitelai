<?php
namespace Common\Controller;
use Think\Controller;
use Common\Basic\CsException;

class AdminapiController extends Controller {
	public function _initialize(){
		$post = I('post.');
		$this->check($post);
	}
	
	protected function jsonReturn($data){
		if (!is_array($data)){
			$data = array(
				'Message'=>$data,
			);
		}
		$data['Error'] = 0;
		!isset($data['Message']) && $data['Message'] = '';
		$this->ajaxReturn($data);
	}

	private function apiSign($str){
		return md5($str);
	}
	
	private function check(&$post){
		//检查公共参数是否齐全
		$version = $post['version'];
		$client = $post['client'];
		$vsign = $post['vsign'];
		$rstr = $post['rstr'];
		if(empty($version) || empty($client) || empty($vsign) || empty($rstr)){
			throw new CsException('缺少公共参数', 2);
		}

		//验证椄口
		$tmpArr = $post;
		$tmpArr['secKey'] = md5('AnApiForFood2China118');
		unset($tmpArr['vsign']);
		
        sort($tmpArr,SORT_STRING);
        $tmpStr = implode($tmpArr);
        $expSign = $this->apiSign($tmpStr);
		if($vsign != $expSign) throw new CsException('接口验证失败-3', 3);
		
		if ($post['token']){
			$admin_id = $this->sessionService()->adminTokenCheck($post['token']);
			$_POST['admin_id'] = $admin_id;
		}
	}
	
	private function sessionService(){
		return D('Session', 'Service');
	}
}