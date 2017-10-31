<?php
namespace Distributor\Controller\Designer;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Distributor\Basic\Purview;

class DesignerCustomController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_designercustom_index',
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
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->designerCustomService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);

		$this->display();
    }
	
	
	
	
	public function delAction($id = 0){
		$info = $this->designerCustomService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerCustomService()->infoDelete($info['id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	
	public function remarkAction($custom_id=0) {
		$info = $this->designerCustomService()->getInfo($custom_id);
		if (empty($info)) {
			$this->error('内容不存在');
		}
		if (IS_POST) {
			$post = I('post.');
			try {
				$this->designerCustomService()->remark($post);
				$this->success('提交成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
	
	private function designerCustomService(){
		return D('DesignerCustom', 'Service');
	}
	
	
	
	
	
}