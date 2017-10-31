<?php
namespace Common\Service;
class SystemService{
	public function getMenu($id){
		return $this->MenuDao()->getRecord($id);
	}
	
	public function menuPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		$MenuDao = $this->MenuDao();
		$count = $MenuDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'menu_id desc' : $params['orderby'];
			$list = $MenuDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function menuCreateOrModify($params){
		$rules = array(
			 array('menu_name','require','名称是必须的！'), 
		);
		$data = array(
			'menu_name'=>trim($params['menu_name']),
			'menu_code'=>trim($params['menu_code']),
			'menu_url'=>trim($params['menu_url']),
			'menu_cls'=>trim($params['menu_cls']),
			'sort_order'=>trim($params['sort_order']),
		);
		
		
		if($params['menu_id'] > 0){
			$data['menu_id'] = $params['menu_id'];
		} else {
			$data['sys_id'] = intval($params['sys_id']);
			$data['parent_id'] = trim($params['parent_id']);
		}
		$MenuDao = $this->MenuDao();
		if (!$MenuDao->validate($rules)->create($data)){
			 throw new \Exception($MenuDao->getError());
		}
		if ($params['menu_id'] > 0){
			M()->startTrans();
			$result = $MenuDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			M()->commit();
		} else {
			$result = $MenuDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function menuDelete($params){
		if ($params['menu_id'] < 1 || $params['sys_id'] < 1) throw new \Exception('缺少参数');
		$map = array(
			'menu_id'=>$params['menu_id'],
			'sys_id'=>$params['sys_id'],
		);
		$result = $this->MenuDao()->deleteRecord($map);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getColumn($id){
		return $this->columnDao()->getRecord($id);
	}
	
	public function columnList($sys_id){
		$map = array(
			'sys_id'=>$sys_id,
		);
		return $this->columnDao()->where($map)->order('sort_order asc')->select();
	}
	
	public function columnCreateOrModify($params){
		$rules = array(
			array('column_name','require','名称是必须的！'),
		);
		$data = array(
			'column_name'=>trim($params['column_name']),
			'menu_list'=>implode(',', $params['menu_list']),
			'sort_order'=>trim($params['sort_order']),
		);
	
	
		if($params['column_id'] > 0){
			$data['column_id'] = $params['column_id'];
		} else {
			$data['sys_id'] = intval($params['sys_id']);
		}
		$columnDao = $this->columnDao();
		if (!$columnDao->validate($rules)->create($data)){
			throw new \Exception($columnDao->getError());
		}
		if ($params['column_id'] > 0){
			M()->startTrans();
			$result = $columnDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			M()->commit();
		} else {
			$result = $columnDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function columnDelete($params){
		if ($params['column_id'] < 1 || $params['sys_id'] < 1) throw new \Exception('缺少参数');
		$map = array(
			'column_id'=>$params['column_id'],
			'sys_id'=>$params['sys_id'],
		);
		$result = $this->columnDao()->deleteRecord($map);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function menuList($sys_id, $action_list, $column_id = 0){
		$column = $this->columnDao()->getRecord($column_id);
		$map = array(
				'sys_id'=>$sys_id,
				'is_show'=>1, //是否显示
		);
		if ($column['sys_id'] == $sys_id && $column['menu_list']){
			$map['_string'] = 'menu_id in ('.$column['menu_list'].') or parent_id = 0';
		}
		$menus = $this->MenuDao()->getMenus($map);
		$list = array();
		foreach($menus as $key=>$val){
			if ($val['parent_id'] == 0){
				$list[$val['menu_code']] = array(
					'cls'=>$val['menu_cls'],
					'txt'=>$val['menu_name'],
					'menu_id'=>$val['menu_id'],
					'sort_order'=>$val['sort_order'],
				);
			} else {
				$parent_code = $menus[$val['parent_id']]['menu_code'];
				$list[$parent_code]['itm'][$val['menu_code']] = array(
					'url'=>$val['menu_url'],
					'txt'=>$val['menu_name'],
					'menu_id'=>$val['menu_id'],
					'sort_order'=>$val['sort_order'],
				);
			}
		}

		
		//权限检测
		$action_list != 'all' && $actions = explode(',', $action_list);
		foreach($list as $ko => $vo){
			if ($action_list != all){
				foreach($vo['itm'] as $kp => $vp){
					if(in_array($kp, $actions)) continue;
					unset($list[$ko]['itm'][$kp]);
				}
			}
			if(count($list[$ko]['itm']) < 1) unset($list[$ko]);
		}
		return $list;
	}
	
	public function topMenuList($sys_id){
		$map = array(
				'sys_id'=>$sys_id,
				'parent_id'=>0,
				'is_show'=>1, //是否显示
		);
		return $this->MenuDao()->getMenus($map);
	}
	
	public function menuIcons(){
		return array(
				'glyphicon-asterisk','glyphicon-plus','glyphicon-minus','glyphicon-euro',
				'glyphicon-cloud','glyphicon-envelope','glyphicon-pencil','glyphicon-glass',
				'glyphicon-music','glyphicon-search','glyphicon-heart','glyphicon-star',
				'glyphicon-star-empty','glyphicon-user','glyphicon-film','glyphicon-th-large',
				'glyphicon-th','glyphicon-th-list','glyphicon-ok','glyphicon-remove','glyphicon-zoom-in',
				'glyphicon-zoom-out','glyphicon-off','glyphicon-signal','glyphicon-cog','glyphicon-trash',
				'glyphicon-home','glyphicon-file','glyphicon-time','glyphicon-road','glyphicon-download-alt',
				'glyphicon-download','glyphicon-upload','glyphicon-inbox','glyphicon-play-circle',
				'glyphicon-repeat','glyphicon-refresh','glyphicon-list-alt','glyphicon-lock','glyphicon-flag',
				'glyphicon-headphones','glyphicon-volume-off','glyphicon-volume-down','glyphicon-volume-up',
				'glyphicon-qrcode','glyphicon-barcode','glyphicon-tag','glyphicon-tags','glyphicon-book',
				'glyphicon-bookmark','glyphicon-print','glyphicon-camera','glyphicon-font','glyphicon-bold',
				'glyphicon-italic','glyphicon-text-height','glyphicon-text-width','glyphicon-align-left',
				'glyphicon-align-center','glyphicon-align-right','glyphicon-align-justify','glyphicon-list',
				'glyphicon-indent-left','glyphicon-indent-right','glyphicon-facetime-video','glyphicon-picture',
				'glyphicon-map-marker','glyphicon-adjust','glyphicon-tint','glyphicon-edit','glyphicon-share',
				'glyphicon-check','glyphicon-move','glyphicon-step-backward','glyphicon-fast-backward',
				'glyphicon-backward','glyphicon-play','glyphicon-pause','glyphicon-stop','glyphicon-forward',
				'glyphicon-fast-forward','glyphicon-step-forward','glyphicon-eject','glyphicon-chevron-left',
				'glyphicon-chevron-right','glyphicon-plus-sign','glyphicon-minus-sign','glyphicon-remove-sign',
				'glyphicon-ok-sign','glyphicon-question-sign','glyphicon-info-sign','glyphicon-screenshot',
				'glyphicon-remove-circle','glyphicon-ok-circle','glyphicon-ban-circle','glyphicon-arrow-left',
				'glyphicon-arrow-right','glyphicon-arrow-up','glyphicon-arrow-down','glyphicon-share-alt',
				'glyphicon-resize-full','glyphicon-resize-small','glyphicon-exclamation-sign','glyphicon-gift',
				'glyphicon-leaf','glyphicon-fire','glyphicon-eye-open','glyphicon-eye-close',
				'glyphicon-warning-sign','glyphicon-plane','glyphicon-calendar','glyphicon-random',
				'glyphicon-comment','glyphicon-magnet','glyphicon-chevron-up','glyphicon-chevron-down',
				'glyphicon-retweet','glyphicon-shopping-cart','glyphicon-folder-close','glyphicon-folder-open',
				'glyphicon-resize-vertical','glyphicon-resize-horizontal','glyphicon-hdd','glyphicon-bullhorn',
				'glyphicon-bell','glyphicon-certificate','glyphicon-thumbs-up','glyphicon-thumbs-down',
				'glyphicon-hand-right','glyphicon-hand-left','glyphicon-hand-up','glyphicon-hand-down',
				'glyphicon-circle-arrow-right','glyphicon-circle-arrow-left','glyphicon-circle-arrow-up',
				'glyphicon-circle-arrow-down','glyphicon-globe','glyphicon-wrench','glyphicon-tasks',
				'glyphicon-filter','glyphicon-briefcase','glyphicon-fullscreen','glyphicon-dashboard',
				'glyphicon-paperclip','glyphicon-heart-empty','glyphicon-link','glyphicon-phone',
				'glyphicon-pushpin','glyphicon-usd','glyphicon-gbp','glyphicon-sort',
				'glyphicon-sort-by-alphabet','glyphicon-sort-by-alphabet-alt','glyphicon-sort-by-order',
				'glyphicon-sort-by-order-alt','glyphicon-sort-by-attributes','glyphicon-sort-by-attributes-alt',
				'glyphicon-unchecked','glyphicon-expand','glyphicon-collapse-down','glyphicon-collapse-up',
				'glyphicon-log-in','glyphicon-flash','glyphicon-log-out','glyphicon-new-window',
				'glyphicon-record','glyphicon-save','glyphicon-open','glyphicon-saved','glyphicon-import',
				'glyphicon-export','glyphicon-send','glyphicon-floppy-disk','glyphicon-floppy-saved',
				'glyphicon-floppy-remove','glyphicon-floppy-save','glyphicon-floppy-open',
				'glyphicon-credit-card','glyphicon-transfer','glyphicon-cutlery','glyphicon-header',
				'glyphicon-compressed','glyphicon-earphone','glyphicon-phone-alt','glyphicon-tower',
				'glyphicon-stats','glyphicon-sd-video','glyphicon-hd-video','glyphicon-subtitles',
				'glyphicon-sound-stereo','glyphicon-sound-dolby','glyphicon-sound-5-1','glyphicon-sound-6-1',
				'glyphicon-sound-7-1','glyphicon-copyright-mark','glyphicon-registration-mark','glyphicon-cloud-download',
				'glyphicon-cloud-upload','glyphicon-tree-conifer','glyphicon-tree-deciduous'
			);
	}
	
	public function getAction($id){
		return $this->actionDao()->getRecord($id);
	}
	
	public function topActionList($sys_id){
		$map = array(
			'sys_id'=>$sys_id,
			'parent_id'=>0,
		);
		return $this->actionDao()->getActions($map);
	}
	
	public function actionPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array(
			'sys_id'=>intval($params['sys_id']),
		);
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		$actionDao = $this->actionDao();
		$count = $actionDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'action_id desc' : $params['orderby'];
			$list = $actionDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function actionList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array(
			'sys_id'=>intval($params['sys_id']),
		);
		$actionDao = $this->actionDao();
		$al = $actionDao->getActions($map);
	    foreach($al as $key=>$val){
			if ($val['parent_id'] == 0){
				$list[$val['action_code']] = $val;
			} else {
				$parent_code = $al[$val['parent_id']]['action_code'];
				$list[$parent_code]['children'][$val['action_code']] = $val;
			}
		}
		return $list;
	}
	
	public function actionCreateOrModify($params){
		if ($params['sys_id'] < 1) throw new \Exception('缺少参数');
		$rules = array(
			 array('action_name','require','名称是必须的！'), 
		);
		$data = array(
			'action_name'=>trim($params['action_name']),
			'action_code'=>trim($params['action_code']),
			'parent_id'=>intval($params['parent_id']),
		);
		
		if($params['action_id'] > 0){
			$data['action_id'] = $params['action_id'];
		} else {
			$data['sys_id'] = $params['sys_id'];
		}
		$actionDao = $this->actionDao();
		if (!$actionDao->validate($rules)->create($data)){
			 throw new \Exception($actionDao->getError());
		}
		if ($params['action_id'] > 0){
			$result = $actionDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
			
		} else {
			$result = $actionDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function actionDelete($params){
		if ($params['action_id'] < 1 || $params['sys_id'] < 1) throw new \Exception('缺少参数');
		$map = array(
			'action_id'=>$params['action_id'],
			'sys_id'=>$params['sys_id'],
		);
		return $this->actionDao()->deleteRecord($map);
	}
	
	private function columnDao(){
		return D('Common/System/SystemColumn');
	}
	
	private function MenuDao(){
		return D('Common/System/SystemMenu');
	}
	
	private function actionDao(){
		return D('Common/System/Action');
	}
}
