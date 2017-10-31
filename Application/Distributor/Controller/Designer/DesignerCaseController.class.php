<?php
namespace Distributor\Controller\Designer;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Distributor\Basic\Purview;

class DesignerCaseController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designerinfo_index',
		);
		$this->sbset($set);
		$list=$this->configService()->findSystemConfigs('design_case');
		foreach($list as $key=>$val){
			$arr=array_filter_value(explode("\r\n",$val));
			$list[$key]=$arr;
		}
		
		$this->assign('config',$list);
		
		//筛选列表
		$type_list=$this->designerCaseService()->typeConfigPagerList(array(),'key,type_name,type_id');
		$type_key_list=$this->designerCaseService()->configGetField(array(),'id,name,type');
		foreach($type_list as $key=>$val){
			foreach($type_key_list as $key2=>$val2){
				if($key==$val2['type']){	
					$new_type_list[$val['type_id']]['type_name']=$val['type_name'];
					$new_type_list[$val['type_id']]['key']=$val['key'];
					$new_type_list[$val['type_id']]['list'][]=$val2;
				}
			}
		}
		$this->assign('type_list',$new_type_list);
		$this->assign('type_key_list',$type_key_list);
		
		
		
		
    }
	
    public function indexAction($designer_id = 0){
    	
		$design_info=$this->designerService()->getInfo($designer_id);
		if(empty($design_info)){
			$this->redirect('Designer/Designerinfo/index');
		}
		$this->assign('info',$design_info);
		
		$get = I('get.');
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
			'designer_id'=>$designer_id,
    	);
    	$result = $this->designerCaseService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);

		$this->display();
    }
	
	public function addAction($designer_id=0){
		
		if(IS_POST){
			$params = I('post.');
			$designer_id=I('designer_id')?I('designer_id'):I('get.designer_id');
			$design_info=$this->designerService()->getInfo($designer_id);
			if(empty($design_info)){
				$this->error('添加失败');
			}
			$params['designer_id']=$designer_id;
			//多张图片
			$gallery_data=array(
				'gallery_pic'=>$_POST['gallery_pic'],
				'gallery_upload_pic'=>$_FILES,
				'gallery_title'=>$_POST['gallery_title'],
				'gallery_sort'=>$_POST['gallery_sort'],
			);
			$gallery=$this->gallery($gallery_data);
			$params['gallery']=$gallery;
			$params['distributor_id']=session('org_id');
			
			
			try {
				$result = $this->designerCaseService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('Designer/DesignerCase/index',array('designer_id'=>$designer_id)),0);
		}
		$this->display('edit');
	}
	
	public function editAction($designer_id = 0){
		$id=I('id')?I('id'):I('get.id');
		$info = $this->designerCaseService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$designer_id=I('designer_id')?I('designer_id'):I('get.designer_id');
			$design_info=$this->designerService()->getInfo($designer_id);
			if(empty($design_info)){
				$this->error('修改失败');
			}
			$gallery_data=array(
				'gallery_pic'=>$_POST['gallery_pic'],
				'gallery_upload_pic'=>$_FILES,
				'gallery_title'=>$_POST['gallery_title'],
				'gallery_sort'=>$_POST['gallery_sort'],
			);
			$gallery=$this->gallery($gallery_data);
			$params['gallery']=$gallery;
			$params['distributor_id']=session('org_id');
			
			
			try {
				$result = $this->designerCaseService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('Designer/DesignerCase/index',array('designer_id'=>$designer_id)),0);
		}
		
		$info['gallery_array']=$info['gallery']!=''?unserialize($info['gallery']):array();
		$this->assign('info', $info);

		$this->display();
	}
	
	
	public function delAction($id = 0){
		$info = $this->designerCaseService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerCaseService()->infoDelete($info['case_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('Designer/DesignerCase/index',array('designer_id'=>$info['designer_id'])),0);
	}
	
	public function gallery($data){
		$edit_data;
		$data['gallery_title'];
		$data['gallery_sort'];
		$data['gallery_pic'];
		$_FILES['gallery_upload_pic'];
		
		foreach($_FILES['gallery_upload_pic'] as $key=>$val){
			foreach($val as $key2=>$val2){
				$file[$key2][$key]=$val2;
			}
		}
		
		foreach($file as $key=>$val){
			if($val['error']==0){
				$fpic = $val;
				$upload = new \Think\Upload(); // 实例化上传类
				$upload->maxSize   =     31457280 ;// 设置附件上传大小
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',);// 设置附件上传类型
				$upload->rootPath  =     UPLOAD_PATH;  // 设置附件上传根目录
				$upload->savePath  =     'edit/'; // 设置附件上传（子）目录
				$upload->subName   =      array('date', 'Ym'); 
				$info = $upload->uploadOne($fpic);
				if($info){
					$upload_img[$key]['pic'] = $info['savepath'].$info['savename'];
				}else{
					$upload_img[$key]['pic'] ='';
				}
			}else{
				$upload_img[$key]['pic'] =$data['gallery_pic'][$key];
			}
		}
		
		foreach($data['gallery_title'] as $key=>$val){
			$edit_data[$key]['pic']=$upload_img[$key]['pic']==''?$data['gallery_pic'][$key]:$upload_img[$key]['pic'];
			$edit_data[$key]['sort']=$data['gallery_sort'][$key];
			$edit_data[$key]['title']=$val;
		}
		
		return serialize($edit_data);
		
	}
	
	public function is_hotAction($id){
		try{
			$this->designerCaseService()->caseIsHot($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('设置成功');
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
	
	
	
	
}