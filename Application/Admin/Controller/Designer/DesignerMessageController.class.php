<?php
namespace Admin\Controller\Designer;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DesignerMessageController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designermessage_index',
		);
		$this->sbset($set);
		$list=$this->configService()->findSystemConfigs('design_case');
		foreach($list as $key=>$val){
			$arr=array_filter_value(explode("\r\n",$val));
			$list[$key]=$arr;
		}
		
		$this->assign('config',$list);
		
    }
	
    public function indexAction($designer_id = 0){
    	session('back_url', __SELF__);
		
		$get = I('get.');
		$this->assign('get', $get);
    	//$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if ($get['start_time']) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if ($get['end_time']) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$result = $this->designerMessageService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		
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
			
			
			try {
				$result = $this->designerCaseService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('Designer/DesignerCase/index',array('designer_id'=>$designer_id)),0);
		}
		$this->display('edit');
	}
	
	public function remarkAction($id=0) {
		$info = $this->designerMessageService()->getInfo($id);
		if (empty($info)) {
			$this->error('内容不存在');
		}
		if (IS_POST) {
			$post = I('post.');
			try {
				$this->designerMessageService()->remark($post);
				$this->success('提交成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->assign('info', $info);
		$this->display();
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
		$info = $this->designerMessageService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerMessageService()->infoDelete($info['id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('Designer/DesignerMessage/index'),0);
	}
	
	
	
	
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage', 'Service');
	}
	
	
	
	
	
}