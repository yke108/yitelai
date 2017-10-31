<?php
namespace Admin\Controller\Point;
use Admin\Controller\FController;
use Common\Basic\Pager;

class GiftGoodsController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'point',
			'ac'=>'point_giftgoods_index',
		);
		$this->sbset($set);
		
	}
	public function indexAction(){
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$map=array();
		$params=array('page'=>$p,'pagesize'=>$size);
		$result=$this->PointGiftService()->infoPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$this->display();
	}
	public function addAction(){
		if(IS_POST){
			$post=$_POST;
			$post['start_time']=strtotime($post['start_time']);
			$post['end_time']=strtotime($post['end_time']);
			$post['add_time']=time();
			
			try{
				$this->PointGiftService()->infoCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'));
		}
		$this->display('edit');
	}
	public function editAction(){
		$id=I('id')?I('id'):I('get.id');
		$info=$this->PointGiftService()->getInfo($id);
		if(empty($info)){$this->error('记录不存在');}
		
		if(IS_POST){
			$post=$_POST;
			$post['id']=I('id')?I('id'):I('get.id');
			$post['start_time']=strtotime($post['start_time']);
			$post['end_time']=strtotime($post['end_time']);
			
			try{
				$this->PointGiftService()->infoCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('编辑成功',U('index'));
		}
		
		$this->assign('info',$info);
		$this->display('edit');
	}
	
	public function delAction(){
		$id=I('id')?I('id'):I('get.id');
		
		//if(IS_POST){
			try{
				$this->PointGiftService()->infoDelete($id);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('删除成功',U('index'));
		//}
		
	}
    
	
	private function PointGiftService(){
		return D('PointGift',"Service");
	}
	
	
}