<?php
namespace Admin\Controller\Site;
use Admin\Controller\FController;
use Think\Controller;

class UploadController extends FController {
	public function _initialize(){
		$this->settingForAll();
		layout(false);
    }
	
	public function indexAction(){
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
				'url' => picurl($info['savepath'].$info['savename']),
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
	
	public function uploadExcelAction(){
		ini_set("memory_limit", "512M"); // 不够继续加大
		//set_time_limit(0);
		ini_set('post_max_size','100M');
		ini_set('upload_max_filesize','100M');
		ini_set('memory_limit','512M');
	
		ini_set('max_execution_time',60);
	
		$filter_array=array('jpg','gif','png','jpeg','mp4','flv','avi','wmv','mkv','3gp','swf','xls','xlsx','html','pdf','txt','sql','tmp','dot','doc','docx','rtf','zip','rar','csv');
	
		$upload = new \Think\Upload(); // 实例化上传类
		$upload->maxSize   =     1024*1024*200 ;// 设置附件上传大小
		$upload->exts      =     $filter_array;// 设置附件上传类型
		$upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
		$upload->savePath  =     'excel/'; // 设置附件上传（子）目录
		$upload->subName   =      array('date', 'Ym');
		$info = $upload->uploadOne($_FILES['Filedata']);
	
		if($info) {
			$result = array(
					'error' => 0,
					'url' => "http://".$_SERVER['HTTP_HOST'].'/'.DK_UPLOAD_URL.$info['savepath'].$info['savename'],
					'path'=>$info['savepath'].$info['savename'],
					'file_name'=>$info['name'],
			);
		} else {
			$result = array(
					'error' => 1,
					'message' => $upload->getError(),
			);
		}
		$this->ajaxReturn($result);
	}
	
	public function uploadFileAction(){
		ini_set("memory_limit", "512M"); // 不够继续加大
		//set_time_limit(0);
		ini_set('post_max_size','100M');
		ini_set('upload_max_filesize','100M');
		ini_set('memory_limit','512M');
		
		ini_set('max_execution_time',60);
		
		$filter_array=array('jpg','gif','png','jpeg','mp4','flv','avi','wmv','mkv','3gp','swf','xls','xlsx','html','pdf','txt','sql','tmp','dot','doc','docx','rtf','zip','rar','csv');
		
		$upload = new \Think\Upload(); // 实例化上传类
		$upload->maxSize   =     1024*1024*200 ;// 设置附件上传大小
		$upload->exts      =     $filter_array;// 设置附件上传类型
		$upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
		$upload->savePath  =     'editor/'; // 设置附件上传（子）目录
		$upload->subName   =      array('date', 'Ym');
		$info = $upload->uploadOne($_FILES['Filedata']);
		
		if($info) {
			$result = array(
					'error' => 0,
					'url' => "http://".$_SERVER['HTTP_HOST'].'/'.DK_UPLOAD_URL.$info['savepath'].$info['savename'],
					'path'=>$info['savepath'].$info['savename'],
					'file_name'=>$info['name'],
			);
		} else {
			$result = array(
					'error' => 1,
					'message' => $upload->getError(),
			);
		}
		$this->ajaxReturn($result);
	}
	
	public function del_pathAction(){
		$path=UPLOAD_PATH.I('path');
		
		if(is_file($path)){
			$bool=unlink($path);	
			if($bool==true){
				$result=array(
					'error'=>0,
					'message'=>'删除成功',
				);
			}else{
				$result=array(
					'error'=>1,
					'message'=>'删除失败',
				);
			}
		}else{
			$result=array(
				'error'=>1,
				'message'=>'删除失败',
			);
		}
		$this->ajaxReturn($result);
	}
}