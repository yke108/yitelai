<?php
namespace Common\Service;
use Common\Basic\CsException;


class DiscoveryService{
	public function getItem($id){
		if ($id < 1) return false;
		return $this->discoveryItemDao()->getRecord($id);
	}
	
	public function itemCreateOrModify($params){
		$data = array(
			'item_id'=>$params['item_id'],
			'item_title'=>trim($params['item_title']),
			'item_tip'=>trim($params['item_tip']),
			'item_icon'=>trim($params['item_icon']),
			'is_open'=>intval($params['is_open']),
			'client_type'=>intval($params['client_type']),
			'version_min'=>intval($params['version_min']),
			'version_max'=>intval($params['version_max']),
			'remark'=>$params['remark'],
		);
		$discoveryItemDao = $this->discoveryItemDao();
		$result = $discoveryItemDao->saveRecord($data);
		if ($result === false){
			throw new CsException('修改失败', 111);
		}
	}
	
	public function itemList($params){
		$map = array();
		$params['is_open'] && $map['is_open'] = $params['is_open'];
		$discoveryItemDao = $this->discoveryItemDao();
		$field = 'item_id, item_title, item_icon, item_tip,item_url,item_type, is_open, sort_order, remark';
		$list = $discoveryItemDao->where($map)->order('sort_order asc')->getField($field);
		return array(
			'list'=>$list,
		);
	}
	
	public function itemSet($params){
		$map = array(
			'item_id'=>$params['item_id'],
			'admin_id'=>$params['admin_id'],
		);
		$discoveryListDao = $this->discoveryListDao();
		$info = $discoveryListDao->findRecord($map);
		if ($info){
			$data = array(
				'log_id'=>$info['log_id'],
			);
			$params['is_home'] && $data['is_home'] = $params['is_home'];
			$params['is_top'] && $data['is_top'] = $params['is_top'];
			if ($discoveryListDao->saveRecord($data) === false){
				throw new CsException('操作失败', 1001);
			}
		} else {
			$data = array(
				'item_id'=>$params['item_id'],
				'admin_id'=>$params['admin_id'],
			);
			$params['is_home'] && $data['is_home'] = $params['is_home'];
			$params['is_top'] && $data['is_top'] = $params['is_top'];
			if ($discoveryListDao->addRecord($data) < 1){
				throw new CsException('操作失败', 1001);
			}
		}
	}
	
	public function myItems($params){
		$map = array(
			'admin_id'=>$params['admin_id'],
		);
		$discoveryListDao = $this->discoveryListDao();
		return $discoveryListDao->where($map)->order('is_home asc, is_top desc')->getField('item_id, is_home, is_top');
	}
	
	private function discoveryItemDao(){
		return D('Common/Discovery/Item');
	}
	
	private function discoveryListDao(){
		return D('Common/Discovery/List');
	}
}