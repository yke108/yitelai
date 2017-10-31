<?php
namespace Common\Basic;

class Menu{
	public function index($action_list){
		$map=array('page'=>1,'pagesize'=>1000);
		$menu=D('System','Service')->menuPagerList($map);
		$menu=node_merge($menu['list'],0,1,'menu_id');
		
		//把菜单格式弄成
		//$list['sb_static']['cls'] = 'glyphicon-dashboard';
//		$list['sb_static']['txt'] = '统计报表';
//		$list['sb_static']['itm']['sb_index_index_index'] = array('index/index', '概况');
		foreach($menu as $key=>$val){
			$list[$val['menu_code']]['cls']=$val['menu_cls'];
			$list[$val['menu_code']]['txt']=$val['menu_name'];
			foreach($val['children'] as $c_key=>$c_val){
				$list[$val['menu_code']]['itm'][$c_val['menu_code']]=array($c_val['menu_url'],$c_val['menu_name']);
			}
		}
		
		//权限检测
		if ($action_list == 'all') return $list;
        $actions = explode(',', $action_list);
		
		foreach($list as $ko => $vo){
			foreach($vo['itm'] as $kp => $vp){
				
				if(strlen($kp) > 3) {
					$pact = substr($kp, 0);
					//$pact=substr($pact,strpos($pact,'_')+1,strlen($pact));
					if(in_array($pact, $actions)) continue;
				}
				unset($list[$ko]['itm'][$kp]);
			}
			if(count($list[$ko]['itm']) < 1) unset($list[$ko]);
		}
		
		return $list;
	}
}