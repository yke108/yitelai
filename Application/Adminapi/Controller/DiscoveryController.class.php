<?php
namespace Adminapi\Controller;
use Common\Controller\AdminapiController as FController;

class DiscoveryController extends FController {
	public function indexAction(){
		$post = I('post.');
		
		//查找全部的
		$params = array(
			'admin_id'=>$post['admin_id'],
		);
		$mylist = $this->discoveryService()->myItems($params);
		$list = array();
		$open_st_list = array(1);
		$params = array(
			'is_open'=>array('in', $open_st_list),
		);
		$result = $this->discoveryService()->itemList($params);
		foreach ($mylist as $po){
			if ($po['is_home'] != 1) continue;
			$vo = $result['list'][$po['item_id']];
			if (empty($vo)) continue;
			$list[] = array(
				'ItemId'=>$vo['item_id'],
				'IsHome'=>1,
				'TopTime'=>$po['is_top'] > 1 ? $po['is_top'] : 0,
				'ItemIcon'=>picurl($vo['item_icon']),
				'ItemTitle'=>$vo['item_title'],
				'ItemTip'=>$vo['item_tip'],
				'ItemRed'=>$itemRed,
				'ItemUrl'=>empty($vo['item_url']) ? '' :DK_DOMAIN.$vo['item_url'],
				'ItemNum'=>intval($itemNum),
			);
		}
		$data = array(
			'List'=>$list,
		);
		$this->jsonReturn($data);
	}
	
	public function listAction(){
		$post = I('post.');
		$open_st_list = array(1);
		$params = array(
			'is_open'=>array('in', $open_st_list),
		);
		$result = $this->discoveryService()->itemList($params);
		$list = array();
		$params = array(
			'admin_id'=>$post['admin_id'],
		);
		$mylist = $this->discoveryService()->myItems($params);
		foreach ($result['list'] as $vo){
			$item = array(
				'ItemId'=>$vo['item_id'],
				'ItemIcon'=>picurl($vo['item_icon']),
				'ItemTitle'=>$vo['item_title'],
				'ItemTip'=>$vo['item_tip'],
				'ItemRed'=>$itemRed,
				'ItemUrl'=>empty($vo['item_url']) ? '' :DK_DOMAIN.$vo['item_url'],
				'ItemNum'=>intval($itemNum),
				'IsHome'=>0,
			);
			if ($mylist[$vo['item_id']]['is_home'] == 1) $item['IsHome'] = 1;
			$list[] = $item;
		}
		$data = array(
			'List'=>$list,
			"RedDot"=>$redDot,
		);
		$this->jsonReturn($data);
	}
	
	public function homeAction(){
		$post = I('post.');
		$params = array(
			'admin_id'=>$post['admin_id'],
			'item_id'=>$post['item_id'],
			'is_home'=>$post['status'] == 1 ? 1 : 2,
		);
		$this->discoveryService()->itemSet($params);
		$data = array(
			'Message'=>'设置成功',
		);
		$this->jsonReturn($data);
	}
	
	public function topAction(){
		$post = I('post.');
		$params = array(
			'admin_id'=>$post['admin_id'],
			'item_id'=>$post['item_id'],
			'is_top'=>$post['status'] == 1 ? NOW_TIME : 1,
		);
		$this->discoveryService()->itemSet($params);
		$data = array(
			'Message'=>'设置成功',
		);
		$this->jsonReturn($data);
	}
	
	private function discoveryService(){
		return D('Discovery', 'Service');
	}
	
	private function configService(){
		return D('Config', 'Service');
	}
}