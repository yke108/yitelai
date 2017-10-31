<?php
namespace Distributor\Controller\Designer;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Distributor\Basic\Purview;
use Common\Basic\Status;

class OrderController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_order_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
   		$get = I('get.');
   		$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    		'keyword'=>$get['keyword'],
    		'status_id'=>$get['status_id'],
    		'admin_id'=>session('uid'),
    		'distributor_id'=>$org_id,
    	);
    	$result = $this->designerService()->orderPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);

		//设计师名称;
		$infoname = $this->designerService()->infoname();
		$this->assign('infoname',$infoname);
		//预约状态名称
		$statusname = $this->designerService()->statusname();
		$this->assign('statusname',$statusname);

		//状态
		$this->assign('statuslist',$this->designerService()->statusList($params));
		//$this->assign('status_list', Status::$designerOrderStatusList);
		
		$this->display();
    }

	public function sheAction($id = 0){
		$info = $this->designerService()->getOrder($id);
		if(empty($info)) $this->error('内容不存在');
		//设计师名称;
		$infoname = $this->designerService()->infoname();
		$this->assign('infoname',$infoname);

		if(IS_POST){
			$params['designer_id'] = I('post.designer_id');
			$xiuname = $infoname[$params['designer_id'] ];
			// var_dump($xiuname);exit;
			$params['order_id'] = $info['order_id'];
			$params['add_time'] = $info['add_time'];

			$params['xiuname'] = $xiuname;
			$params['admin_id'] = session('uid');
			try {
				$result = $this->designerService()->shexiu($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		//设计师//
		$params = array(
			'distributor_id' => $this->org_id,
			'admin_id' => session('uid'),
		);
		$this->assign('infolist',$this->designerService()->infoList($params));

		$this->display();
	}

	public function zhuangAction($id = 0){
		$info = $this->designerService()->getOrder($id);
		if(empty($info)) $this->error('内容不存在');
		//预约状态名称
		$statusname = $this->designerService()->statusname();
		$this->assign('statusname',$statusname);
	

		if(IS_POST){
			$params['status_id'] = I('post.status_id');
			$zxiuname = $statusname[$params['status_id'] ];

			$params['order_id'] = $info['order_id'];
			$params['add_time'] = $info['add_time'];
			$params['user_id'] = $info['user_id'];
			$params['zxiuname'] = $zxiuname;
			$params['admin_id'] = session('uid');
			try {
				$result = $this->designerService()->zhuangxiu($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		//状态
		$this->assign('statuslist',$this->designerService()->statusList($params));
		$this->display();
	}

	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['distributor_id'] = $this->org_id;
			$params['admin_id'] = session('uid');
 			try {
				$result = $this->designerService()->orderCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}

		//设计师
		$params = array(
			'distributor_id' => $this->org_id,
			'admin_id' => session('uid'),
		);
		$this->assign('infolist',$this->designerService()->infoList($params));
		//状态
		$this->assign('statuslist',$this->designerService()->statusList($params));

		$this->display('edit');
	}
	
	public function editAction($id = 0){

		$info = $this->designerService()->getOrder($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		//日志
		$params = array(
 			'order_id'=>$info['order_id'],
		); 
		$this->assign('orderloglist',$this->designerService()->orderlogList($params));
		//设计师名称;
		$infoname = $this->designerService()->infoname();
		$this->assign('infoname',$infoname);
		//预约状态名称
		$statusname = $this->designerService()->statusname();
		$this->assign('statusname',$statusname);

		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->designerService()->getOrder($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->designerService()->orderDelete($info['order_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
}