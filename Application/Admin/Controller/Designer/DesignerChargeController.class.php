<?php
namespace Admin\Controller\Designer;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DesignerChargeController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designercharge_index',
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
    	
		
		$get = I('get.');
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->designerService()->chargePagerList($params);
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

			
			try {
				$result = $this->designerService()->chargeCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('Designer/DesignerCharge/index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($designer_id = 0){
		$id=I('id')?I('id'):I('get.id');
		$info = $this->designerService()->chargeGetInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['charge_id']=$id;
			try {
				$result = $this->designerService()->chargeCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('Designer/DesignerCharge/index'),0);
		}
		
		
		$this->assign('info', $info);

		$this->display();
	}
	
	
	public function delAction($id = 0){
		$info = $this->designerService()->chargeGetInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerService()->chargeDelete($info['id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('Designer/DesignerCharge/index'),0);
	}
	
	
	
	
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
	
	private function designerSpaceService(){
		return D('DesignerSpace', 'Service');
	}
	
	
	
	
	
}