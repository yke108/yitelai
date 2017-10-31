<?php
namespace Home\Controller\Material;
use Home\Controller\BaseController;
use Common\Basic\Genre;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		
		$cat_list = $this->materialCatService()->getAllList();
		$this->assign('cat_list', $cat_list['list']);
    }
	
    public function indexAction(){ /*素材列表*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	if ($get['id']) {
    		$cat = $this->materialCatService()->getInfo($get['id']);
    		$this->assign('cat', $cat);
    	
    		if ($cat['parent_id'] > 0) {
    			$parent = $this->materialCatService()->getInfo($cat['parent_id']);
    		}else {
    			$parent = $cat;
    		}
    		$this->assign('parent', $parent);
    	
    		//下级分类
    		$map = array('parent_id'=>$parent['cat_id']);
    		$sub_list = $this->materialCatService()->selectAllList($map);
    		$this->assign('sub_list', $sub_list);
    	}else {
    		$material_count = $this->materialService()->getCount();
    		$this->assign('material_count', $material_count);
    	}
    	
    	//素材列表
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    			'user_id'=>$this->user['user_id'],
    			'is_show'=>1,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if ($get['id']) {
    		$params['cat_id'] = $get['id'];
    	}
    	if ($get['label']) {
    		$params['label_id'] = $get['label'];
    	}
    	if ($get['designer']) {
    		$params['designer_id'] = $get['designer'];
    	}
    	if (isset($get['is_free'])) {
    		if ($get['is_free'] == 1) {
    			$map['down_points'] =  0;
    		}else {
    			$map['down_points'] =  array('gt', 0);
    		}
    		$params['map'] = $map;
    	}
    	if ($get['sort_order']) {
    		switch ($get['sort_order']) {
    			case 'down': $params['orderby'] = 'down_count DESC'; break;
    			case 'collect': $params['orderby'] = 'collect_count DESC'; break;
    		}
    	}
    	$datas = $this->materialService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$this->assign('count', $datas['count']);
    	
    	if (IS_AJAX) {
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	
    	$this->assign('page_title', '素材列表');
    	$this->display();
    }
	
	public function infoAction($id = 0){ /*素材详情*/
		$info = $this->materialService()->getInfo($id);
		if (empty($info)) {
			$this->error('素材不存在');
		}
		$this->assign('info', $info);
		
		//统计浏览数
		$this->materialService()->viewCount($info['material_id']);
		
		//分类
		$cat = $this->materialCatService()->getInfo($info['cat_id']);
		$this->assign('cat', $cat);
		
		//相关图片
		$params = array(
				'pagesize'=>5,
				'cat_id'=>$cat['cat_id'],
				'is_show'=>1,
		);
		$datas = $this->materialService()->getPagerList($params);
		$this->assign('rela_list', $datas['list']);
		
		//设计师
		$designer_info = $this->designerService()->getInfo($info['designer_id']);
		$this->assign('designer_info', $designer_info);
		
		//设计师素材
		$params = array(
				'pagesize'=>3,
				'designer_id'=>$designer_info['designer_id'],
				'is_show'=>1,
		);
		$datas = $this->materialService()->getPagerList($params);
		$this->assign('designer_material_list', $datas['list']);
		
		$this->assign('page_title', '素材详情');
		$this->display();
	}
	
	public function editAction($id = 0){ /*编辑素材*/
		$info = $this->materialService()->getInfo($id);
		if (empty($info)) {
			$this->error('素材不存在');
		}
		
		if (IS_POST) {
			$post = I('post.');
			$post['material_id'] = $post['id'];
			unset($post['id']);
			$post['material_image'] = $post['material_gallery'][0];
			$post['material_gallery'] = implode('#', $post['material_gallery']);
			$post['is_show'] = 1;
			try {
				$this->materialService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功', session('wap_from_url'));
		}
		
		//素材
		$this->assign('info', $info);
		
		//当前分类
		$cat = $this->materialCatService()->getInfo($info['cat_id']);
		$this->assign('cat', $cat);
		
		//分类列表
		$map = array();
		$result = $this->materialCatService()->getAllList();
		$cat_list = $children = array();
		foreach ($result['list'] as $v) {
			$cat_list[$v['cat_id']] = $v;
			if ($v['cat_id'] == $cat['parent_id']) {
				$children = $v['children'];
			}
		}
		$this->assign('cat_list', $cat_list);
		$this->assign('children', $children);
		
		$this->assign('page_title', '素材修改');
		$this->display();
	}
	
	public function downcheckAction($id = 0){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', $this->login_url);
		}
		
		$info = $this->materialService()->getInfo($id);
		if(empty($info)){
			$this->error('文件不存在');
		}
		
		//判断积分是否足够
		if ($info['down_points'] > $this->user['pay_points']) {
			$this->error('积分不够');
		}
		
		$this->success('正在转向下载', DK_DOMAIN.U('download', array('id'=>$info['material_id'])), array('down_count'=>$info['down_count']+1));
	}
	
	public function downloadAction($id = 0){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', $this->login_url);
		}
		
		$info = $this->materialService()->getInfo($id);
		if(empty($info)){
			$this->error('文件不存在');
		}
		
		if ($info['down_points'] > 0) {
			//判断积分是否足够
			if ($info['down_points'] > $this->user['pay_points']) {
				$this->error('积分不够');
			}
			
			//扣除积分
			$PointLogic = $this->PointLogic();
			$params_point = array(
					'user_id'=>$this->user['user_id'],
					'point_old'=>$this->user['pay_points'],
					'point'=>$info['down_points'],
					'type'=>$PointLogic::PointTypeDownLoad,
					'ref_id'=>$info['material_id'],
			);
			try {
				$PointLogic->reduce($params_point);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//统计下载
		$result = $this->materialService()->downCount($info['material_id'], $this->user['user_id']);
		if ($result === false) {
			$this->error('下载失败');
		}
		
		//下载
		$file = UPLOAD_PATH.$info['upload_path'];
		$file_name = $info['file_name'];
		if(is_file($file)){
			$length = filesize($file);
			$file_info=pathinfo($file);
			$file_name=($file_name==''?time().'.'.$file_info['extension']:$file_name.'.'.$file_info['extension']);
			$showname = $file_name;
			header("Content-Description: File Transfer");
			header('Content-type: ' . $file_info['type']);
			header('Content-Length:' . $length);
			if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
				header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
			} else {
				header('Content-Disposition: attachment; filename="' . $showname . '"');
			}
			readfile($file);
			exit;
		} else {
			exit('文件不存在！');
		}
	}
	
	public function delAction($id = 0){
		try {
			$this->materialService()->delete($id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialService(){
		return D('Material', 'Service');
	}
	
	private function materialCatService(){
		return D('MaterialCat', 'Service');
	}
	
	private function materialLabelService(){
		return D('MaterialLabel', 'Service');
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function PointLogic(){
		return D('Point','Logic');
	}
}