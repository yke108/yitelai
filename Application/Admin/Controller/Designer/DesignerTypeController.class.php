<?php
namespace Admin\Controller\Designer;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DesignerTypeController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designertype_index',
		);
		$this->sbset($set);
		$config_type=$this->designerTypeService()->getTypeField(array(),'key,type_name');
		$this->assign('config_type',$config_type);
		
    }
	
    public function indexAction($designer_id = 0){
    	$config_type=$this->designerTypeService()->getTypeField(array(),'key,type_name');
		$this->assign('config_type',$config_type);
		
		$get = I('get.');
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>1000,
    	);
    	$result = $this->designerTypeService()->typeValuePagerList($params);
		
    	if ($result['count'] > 0){
    		$list=$result['list'];
			foreach($list as $key=>$val){
				$new_list[$val['type']]['list'][]=$val;
				$new_list[$val['type']]['name']=$config_type[$val['type']];
			}
			$this->assign('list', $new_list);
    		//$pager = new Pager($result['count'], $result['pagesize']);
//    		$this->assign('pager', $pager->show());
    	}
		
		$this->assign('get', $get);

		$this->display();
    }
	
	public function addAction($designer_id=0){
		
		if(IS_POST){
			$params = I('post.');

			
			try {
				$result = $this->designerTypeService()->typeValueCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$id=I('id')?I('id'):I('get.id');
		$info = $this->designerTypeService()->getTypeValue($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['id']=$id;
			try {
				$result = $this->designerTypeService()->typeValueCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功');
		}
		
		
		$this->assign('info', $info);

		$this->display();
	}
	
	
	public function delAction($id = 0){
		$info = $this->designerTypeService()->getTypeValue($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerTypeService()->typeValueDelete($info['id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function is_key_indexAction($id){
		try{
			$this->designerTypeService()->typeValueIsIndex($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('推荐首页成功');
	}
		
	
	private function designerTypeService(){
		return D('DesignerType', 'Service');
	}
	
	
	
}