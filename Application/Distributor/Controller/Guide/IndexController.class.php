<?php
namespace Distributor\Controller\Guide;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {

	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'guide',
			'ac'=>'guide_index_index',
		);
		$this->sbset($set);
	}
	
    public function indexAction($id = 0){
		
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$in_map=array('data_type'=>2,'oper_status'=>1);
		$file_name=I('file_name')?I('file_name'):I('get.file_name');
		if($file_name!=''){
			$in_map['file_name']=array('like',"%".$file_name."%");
		}
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		if($start_time!=''){
			$start_time=strtotime(date("Y-m-d 00:00:00",strtotime($start_time)));
			$in_map['add_time'][]=array('gt',$start_time);
		}
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		if($end_time!=''){
			$end_time=strtotime(date("Y-m-d 23:59:59",strtotime($end_time)));
			$in_map['add_time'][]=array('lt',$end_time);
		}
		$map=array('map'=>$in_map,'page'=>$p,'pagesize'=>$size);
		
		$result=D('Guide','Service')->getPagerList($map);
		foreach($result['list'] as $key=>$val){
			$result['list'][$key]['file_path']="http://".$_SERVER['HTTP_HOST'].DK_UPLOAD_URL.'/'.$val['file_path'];
			$result['list'][$key]['powers_arr']=$val['del_powers']!=''?explode(',',$val['del_powers']):array();
		}
		
		$pager=new Pager($result['count'],$size);
		$pager->setConfig('header','');
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$get = I('get.');
		$this->assign('get', $get);
		$this->display();
    }
	
	public function downloadAction($id = 0){
		$info=D('Guide','Service')->getInfo($id);
        if(empty($info) || $info['file_path']==''){
			 exit('文件不存在！');
		}
		$file = UPLOAD_PATH.$info['file_path'];  
		$file_name = $info['file_name'];
        
		if(is_file($file)){
			$length = filesize($file);
			$file_info=pathinfo($file);
			$file_name=($file_name==''?time().'.'.$file_info['extension']:$file_name);
			$showname = $file_name; //ltrim(strrchr($file,'/'),'/');
			header("Content-Description: File Transfer");
			header('Content-type: ' . $file_info['type']);
			header('Content-Length:' . $length);
			 if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
				 header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
			 } else {
				 header('Content-Disposition: attachment; filename="' . $showname . '"');
			 }
			 readfile($file);
			 exit;
		 } else {
			 exit('文件不存在！');
		 }
	}

}