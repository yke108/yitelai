<?php
namespace Distributor\Controller\Designer;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Distributor\Basic\Purview;

class DesignerinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designerinfo_index',
		);
		$this->sbset($set);
		$type_result=$this->designerTypeService()->typeValuePagerList(array('pagesize'=>1000));
		if($type_result['count']>0){
			$config_type=$this->designerTypeService()->getTypeField(array(),'key,type_name');
			foreach($type_result['list'] as $key=>$val){
				$type_list[$val['type']]['list'][]=$val;
				$type_list[$val['type']]['name']=$config_type[$val['type']];
				$type_list[$val['type']]['key']=$val['type'];
			}
			$this->assign('type_list',$type_list);
		}
		//var_dump($type_list['space']['list']);die();
		//var_dump($charge);die();
		
		$list = $this->designerCaseService()->configAllList();
		if ($list){
			foreach($list as $key=>$val){
				$new_list[$val['type']]['list'][]=$val;
				$new_list[$val['type']]['name']=$config_type[$val['type']];
			}
			$this->assign('case_list', $new_list);
		}
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    		'keyword'=>$get['keyword'],
    		'admin_id'=>session('uid'),
    		'distributor_id'=>$org_id,
    	);
    	$result = $this->designerService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);

		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['distributor_id'] = $this->org_id;
			$params['demand']=$params['demand']!=''?','.implode(',',$params['demand']).',':'';
			//$params['decorate']=$params['decorate']!=''?','.implode(',',$params['decorate']).',':'';
			
			try {
				$result = $this->designerService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->designerService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['designer_id'] = $info['designer_id'];
			$params['add_time'] = $info['add_time'];
			$params['update_time'] = $info['update_time'];
			$params['demand']=!empty($params['demand'])?','.implode(',',$params['demand']).',':'';
			//$params['decorate']=$params['decorate']!=''?','.implode(',',$params['decorate']).',':'';
			
			try {
				$result = $this->designerService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$info['demand_arr'] = explode(',', trim($info['demand']));
		$info['decorate_arr'] = explode(',', trim($info['decorate']));
		$this->assign('info', $info);

		$this->display();
	}
	
	
	public function delAction($id = 0){
		$info = $this->designerService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerService()->infoDelete($info['designer_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	public function caseConfigAction(){
		if(IS_POST){
			$post=I('post.');
			try{
				$this->configService()->setConfig($post,'design_case');
			}catch(\Exception $e){
				$this->error($e->getMessage);
			}
			$this->success('修改成功');
		}
		$list=$this->configService()->findSystemConfigs('design_case','fkey,fval,ftitle,fdesc,field_type');
		
		$this->assign('config',$list);
		
		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designerinfo_caseConfig',
		);
		$this->sbset($set);
		
		$this->display();
	}
	
	public function configAction(){
		if(IS_POST){
			$post=I('post.');
			try{
				$this->configService()->setConfig($post,'design');
			}catch(\Exception $e){
				$this->error($e->getMessage);
			}
			$this->success('修改成功');
		}
		$list=$this->configService()->findSystemConfigs('design','fkey,fval,ftitle,fdesc,field_type');
		$this->assign('config',$list);
		
		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designerinfo_config',
		);
		$this->sbset($set);
		
		$this->display();
	}
	
	public function page_topAction(){
		$design_id=I('designer_id')?I('designer_id'):I('get.designer_id');
		$design_info=$this->designerService()->getInfo($design_id);
		if(empty($design_info)){
			$this->error('失败');
		}
		
		$save_data=array('is_page_top'=>($design_info['is_page_top']==1?0:1),'designer_id'=>$design_id);
		$result=$this->designerService()->infoUpdate($save_data);
		if($result==false){
			$this->error('失败');
		}else{
			$this->ajaxReturn(array('status'=>3,'force_redirect_page'=>1,'url'=>U('index')));
		}
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerTypeService(){
		return D('DesignerType','Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
}