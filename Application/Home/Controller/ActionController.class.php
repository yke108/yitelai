<?php
namespace Home\Controller;
use Think\Controller;

class ActionController extends Controller {
	public $sys_id = 5;

	public function indexAction(){ /*权限批量更新*/
		$file_list = array();
		$this->read_all_dir(__DIR__, $file_list);
		$result = $actions = [];
		foreach ($file_list as $ko => $vo){
			if (dirname($vo) == __DIR__) continue;
			$result[$ko]['filename'] = str_replace('Controller.class.php','',basename($vo));
			$content = file_get_contents($vo);
			$lines = explode("\n", $content);
			foreach ($lines as $vo){
				$vo = trim($vo);
				if (strpos($vo, 'namespace') !== false){
					$vo = str_replace(['namespace ', 'Home\\Controller\\', ';'], '', $vo);
					$result[$ko]['namespace'] = trim($vo);
				} elseif (strpos($vo, 'function ') !== false 
						&& strpos($vo, 'Action') > 0){
					if (strpos($vo, 'private ') !== false || strpos($vo, 'protected ') !== false) continue;
					$arr = explode('Action', $vo);
					$vo = str_replace(['function ', 'public '], '', $arr[0]);
					$vo = $result[$ko]['namespace'].'/'.$result[$ko]['filename'].'/'.trim($vo);
					$nms = explode('*', $arr[1]);
					$name = trim($nms[1]);
					if ($name == 'NoPurview' || empty($name)) continue;
					$actions[] = [
						'action_code'=>strtolower($vo),
						'action_name'=>$name,
					];
				}
			}
		}
		$this->actSave($actions);
	}
	
	private function actSave($actions){
		$map = [
			'parent_id'=>0,
			'sys_id'=>$this->sys_id,
		];
		$top_action = M('SystemAction')->where($map)->find();
		if (empty($top_action)){
			$data = [
				'action_name'=>'未分组',
				'action_code'=>'nogroup',
				'sys_id'=>$this->sys_id,
			];
			$top_action['action_id'] = M('SystemAction')->add($data);
		}
		foreach ($actions as $vo){
			$map = [
				'action_code'=>$vo['action_code'],
				'sys_id'=>$this->sys_id,
			];
			$info = M('SystemAction')->where($map)->find();
			if ($info){
				if (empty($info['action_name']) && $vo['action_name']){
					$data = [
						'action_id'=>$info['action_id'],
						'action_name'=>$vo['action_name'],
					];
					M('SystemAction')->save($data);
				} 
			} else {
				$data = [
					'action_id'=>$info['action_id'],
					'parent_id'=>$top_action['action_id'],
					'action_code'=>$vo['action_code'],
					'action_name'=>$vo['action_name'],
					'sys_id'=>$this->sys_id,
				];
				M('SystemAction')->add($data);
			}
		}
	}
	
	function read_all_dir ( $dir, &$result ){
		$handle = opendir($dir);
		if ( $handle ){
			while ( ( $file = readdir ( $handle ) ) !== false ){
				if ( $file != '.' && $file != '..'){
					$cur_path = $dir . DIRECTORY_SEPARATOR . $file;
					if ( is_dir ( $cur_path ) ){
						$this->read_all_dir ( $cur_path, $result );
					}
					else{
						$result[] = $cur_path;
					}
				}
			}
			closedir($handle);
		}
	}
	
}
