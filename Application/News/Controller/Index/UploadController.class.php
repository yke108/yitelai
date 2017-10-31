<?php
namespace News\Controller\Index;
use News\Controller\WapController;

class UploadController extends WapController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction() {
    	$upload = new \Think\Upload(); // 实例化上传类
    	$upload->maxSize   =     31457280 ;// 设置附件上传大小
    	$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',);// 设置附件上传类型
    	$upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
    	$upload->savePath  =     'editor/'; // 设置附件上传（子）目录
    	$upload->subName   =      array('date', 'Ym');
    	$info = $upload->uploadOne($_FILES['imgFile']);
    	if($info) {
    		$result = array(
    				'error' => 0,
    				'url' => picurl($info['savepath'].$info['savename'], 'b120'),
    				'short_url'=>$info['savepath'].$info['savename'],
    		);
    	} else {
    		$result = array(
    				'error' => 1,
    				'message' => $upload->getError(),
    		);
    	}
    	$this->ajaxReturn($result);
    }
}