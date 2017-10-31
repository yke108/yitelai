<?php
namespace Admin\Controller\Company;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DesignerinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'company',
			'ac'=>'company_index_index',
		);
		$this->sbset($set);
		$config_type=$this->designerTypeService()->getTypeValueField(array(),'id,name');
		$this->assign('config_type',$config_type);
		
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
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['distributor_id'])) {
    		$params['distributor_id'] = $get['distributor_id'];
    	}
    	$result = $this->designerinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);

		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->designerinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index', array('distributor_id'=>$params['distributor_id'])),0);
		}
		
		$get = I('get.');
		$this->assign('distributor_id', $get['distributor_id']);
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->designerinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['designer_id'] = $info['designer_id'];
			$params['add_time'] = $info['add_time'];
			$params['update_time'] = $info['update_time'];
			// var_dump($params);exit;
			try {
				$result = $this->designerinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index', array('distributor_id'=>$info['distributor_id'])),0);
		}
		$this->assign('info', $info);
		$this->assign('distributor_id', $info['distributor_id']);

		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->designerinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerinfoService()->infoDelete($info['designer_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index', array('distributor_id'=>$info['distributor_id'])),0);
	}
	
	public function change_statusAction($id){
		try{
			$this->designerinfoService()->designerIsIndex($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('推荐首页成功');
	}
	
	public function is_key_indexAction($id){
		try{
			$this->designerinfoService()->designerIsKeyIndex($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('推荐首页成功');
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
		$design_info=$this->designerinfoService()->getInfo($design_id);
		if(empty($design_info)){
			$this->error('失败');
		}
		
		$save_data=array('is_page_top'=>($design_info['is_page_top']==1?0:1),'designer_id'=>$design_id);
		$result=$this->designerinfoService()->infoUpdate($save_data);
		if($result==false){
			$this->error('失败');
		}else{
			$this->success('成功');
		}
	}
	
	private function designerinfoService(){
		return D('Designer', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function designerTypeService(){
		return D('DesignerType','Service');
	}
}