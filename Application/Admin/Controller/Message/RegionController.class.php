<?php
namespace Admin\Controller\Message;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class RegionController extends FController {
	public function _initialize(){
		parent::_initialize();	
		$set = array(
			'in'=>'message',
			'ac'=>'message_region_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$map = array('msg_type'=>Status::MsgTypeRegion);
    	if ($get['keyword']) {
    		$map['keyword'] = $get['keyword'];
    	}
    	if ($get['cat_id']) {
    		$map['cat_id'] = $get['cat_id'];
    	}
    	if ($get['start_time']) {
    		$map['add_time'][] = array('egt', strtotime($get['start_time']));
    	}
    	if ($get['end_time']) {
    		$map['add_time'][] = array('elt', strtotime($get['end_time']) + 86400);
    	}
    	
    	$count = $this->distributorMessageService()->searchMessagesCount($map);
    	if ($count) {
    		$page = intval(I('p')) ? intval(I('p')) : 1;
    		$pagesize = $this->pagesize;
    		$list = $this->distributorMessageService()->searchMessages($map, 'add_time DESC', $page, $pagesize);
    		$pager = new Pager($count, $pagesize);
    		$this->assign('list', $list);
    		$this->assign('pager', $pager->show());
    	}
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = $_POST;
			
			if (empty($post['area_id'])) $this->error('请选择区域');
			
			$map = array('area_id'=>$post['area_id']);
			$distributor_list = $this->distributorService()->getAllList($map);
			if (empty($distributor_list)) $this->error('该区域没有店铺');
			$distributor_ids = array_keys($distributor_list);
			
			$post['admin_id'] = session('uid');
			$post['msg_type'] = Status::MsgTypeRegion;
			try{
				$this->distributorMessageService()->addAllMessage($distributor_ids,$post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			$this->success('保存成功', U('index'));
		}
		
		//所有区域
		$area_list = $this->areaService()->getAllList();
		$this->assign('area_list', $area_list);
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$map = array(
			'msg_id'=>$id,
		);
		$info = $this->distributorMessageService()->getMessageInfo($map);
		if(empty($info)){
			$this->error('消息不存在');
		}
		
		if(IS_POST){
			$data = $_POST;
			$data['admin_id'] = session('uid');
			$data['update_time'] = NOW_TIME;
			try {
				$res = $this->distributorMessageService()->modify($map, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		//分类
		$cat_list = $this->messageCatService()->catOptionList('', '', $info['cat_id']);
		$this->assign('cat_list', $cat_list);
		
		$this->assign('info', $info);
		$this->display();
	}
	
    public function delAction($id = 0){
		try {
			$this->distributorMessageService()->messageDelete($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
    }
	
	public function distributorsAction(){
		layout('Layout/sel');
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$map=array();
		$params=array('map'=>$map,'pagesize'=>$size,'page'=>$p);
		$result=$this->distributorService()->getPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		$this->display();
	}
	
	private function distributorMessageService(){
		return D('DistributorMessage','Service');
	}
	
	private function messageCatService(){
		return D('DistributorMessageCat','Service');
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function areaService(){
		return D('Area', 'Service');
	}
}