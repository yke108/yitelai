<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;
use Common\Basic\CsException;

class FileController extends FController {
	private $upload; //上传实例
	
	public function _initialize(){
		$this->upload = new \Think\Upload(); // 实例化上传类 
	}
	
	public function indexAction(){
		$this->upload->maxSize  = 31457280 ; //设置附件上传大小
		$this->upload->savePath = 'file/';
		$this->upload->exts     = array('jpg', 'gif', 'png', 'jpeg');
		$data = $this->uploadFile();
		$this->jsonReturn($data);
	}
	
	public function avatarAction(){
		$this->upload->maxSize  = 31457280 ; //设置附件上传大小
		$this->upload->savePath = 'avatar/';
		$this->upload->exts     = array('jpg', 'gif', 'png', 'jpeg');
		$data = $this->uploadFile();
		$this->jsonReturn($data);
	}
	
	public function imageAction(){
		$this->upload->maxSize  = 31457280 ; //设置附件上传大小
		$this->upload->savePath = 'image/';
		$this->upload->exts     = array('jpg', 'gif', 'png', 'jpeg');
		$data = $this->uploadFile();
		$this->jsonReturn($data);
	}
	
	private function uploadFile(){
		$this->upload = new \Think\Upload(); // 实例化上传类
		$this->upload->rootPath  =     UPLOAD_PATH; // 设置附件上传根目录
		$this->upload->subName   =     array('date', 'Ym');
		$info = $this->upload->upload();
		if(!$info) {
			throw new CsException($upload->getError(), 90001);
		}
		$list = array();
		foreach($info as $file){
			$filepath = $file['savepath'].$file['savename'];
			$list[] = array(
				'FilePath'=>$filepath,
				'FileUrl'=>picurl($filepath),
			);
		}
		return array(
			'List'=>$list,
			'Message'=>'上传成功',
		);
	}
}