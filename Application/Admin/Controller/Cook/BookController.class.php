<?php
namespace Admin\Controller\Cook;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class BookController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'cook',
			'ac'=>'cook_book_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//$params = array();
    	//$this->assign('all_cat_list', $this->cookCatService()->catOptionList($params, $get['cat_id']));
    	$this->publicAssign();
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	/* if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	} */
    	if (!empty($get['label_id'])) {
    		$params['label_id'] = $get['label_id'];
    	}
    	$datas = $this->cookBookService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			
			//主料
			$main_material = array();
			if ($post['main_material']['picture']) {
				foreach ($post['main_material']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['main_material']['name'][$k]) || !empty($post['main_material']['dosage'][$k])) {
						if (empty($v)) $this->error('请上传主料图片');
						if (empty($post['main_material']['name'][$k])) $this->error('请填写用料名称');
						if (empty($post['main_material']['dosage'][$k])) $this->error('请填写用量');
							
						$main_material[] = array(
								'picture'=>$v,
								'name'=>$post['main_material']['name'][$k],
								'dosage'=>$post['main_material']['dosage'][$k],
						);
					}
				}
			}
			if ($main_material) {
				$post['material']['main_material'] = $main_material;
			}
				
			//辅料
			$sub_material = array();
			if ($post['sub_material']['picture']) {
				foreach ($post['sub_material']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['sub_material']['name'][$k]) || !empty($post['sub_material']['dosage'][$k])) {
						if (empty($v)) $this->error('请上传辅料图片');
						if (empty($post['sub_material']['name'][$k])) $this->error('请填写用料名称');
						if (empty($post['sub_material']['dosage'][$k])) $this->error('请填写用量');
							
						$sub_material[] = array(
								'picture'=>$v,
								'name'=>$post['sub_material']['name'][$k],
								'dosage'=>$post['sub_material']['dosage'][$k],
						);
					}
				}
			}
			if ($sub_material) {
				$post['material']['sub_material'] = $sub_material;
			}
				
			if ($post['material']) {
				$post['material'] = serialize($post['material']);
			}
				
			//步骤
			$steps = array();
			if ($post['steps']['picture']) {
				foreach ($post['steps']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['steps']['description'][$k])) {
						if (empty($v)) $this->error('请上传步骤图');
						if (empty($post['steps']['description'][$k])) $this->error('请填写步骤描述');
							
						$steps[] = array(
								'picture'=>$v,
								'description'=>$post['steps']['description'][$k],
						);
					}
				}
			}
			if ($steps) {
				$post['steps'] = serialize($steps);
			}
			
			$post['user_id'] = session('uid');
			try {
				$result = $this->cookBookService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->publicAssign();
		
		$this->display('edit');
	}
	
	public function editAction($book_id = 0){
		$info = $this->cookBookService()->getInfo($book_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			
			//主料
			$main_material = array();
			if ($post['main_material']['picture']) {
				foreach ($post['main_material']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['main_material']['name'][$k]) || !empty($post['main_material']['dosage'][$k])) {
						if (empty($v)) $this->error('请上传主料图片');
						if (empty($post['main_material']['name'][$k])) $this->error('请填写用料名称');
						if (empty($post['main_material']['dosage'][$k])) $this->error('请填写用量');
							
						$main_material[] = array(
								'picture'=>$v,
								'name'=>$post['main_material']['name'][$k],
								'dosage'=>$post['main_material']['dosage'][$k],
						);
					}
				}
			}
			if ($main_material) {
				$post['material']['main_material'] = $main_material;
			}
			
			//辅料
			$sub_material = array();
			if ($post['sub_material']['picture']) {
				foreach ($post['sub_material']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['sub_material']['name'][$k]) || !empty($post['sub_material']['dosage'][$k])) {
						if (empty($v)) $this->error('请上传辅料图片');
						if (empty($post['sub_material']['name'][$k])) $this->error('请填写用料名称');
						if (empty($post['sub_material']['dosage'][$k])) $this->error('请填写用量');
							
						$sub_material[] = array(
								'picture'=>$v,
								'name'=>$post['sub_material']['name'][$k],
								'dosage'=>$post['sub_material']['dosage'][$k],
						);
					}
				}
			}
			if ($sub_material) {
				$post['material']['sub_material'] = $sub_material;
			}
			
			if ($post['material']) {
				$post['material'] = serialize($post['material']);
			}
			
			//步骤
			$steps = array();
			if ($post['steps']['picture']) {
				foreach ($post['steps']['picture'] as $k => $v) {
					if (!empty($v) || !empty($post['steps']['description'][$k])) {
						if (empty($v)) $this->error('请上传步骤图');
						if (empty($post['steps']['description'][$k])) $this->error('请填写步骤描述');
							
						$steps[] = array(
								'picture'=>$v,
								'description'=>$post['steps']['description'][$k],
						);
					}
				}
			}
			if ($steps) {
				$post['steps'] = serialize($steps);
			}
			
			$post['user_id'] = session('uid');
			try {
				$result = $this->cookBookService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->publicAssign();
		
		$this->display();
	}
	
	public function delAction($book_id = 0){
		try {
			$result = $this->cookBookService()->infoDelete($book_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功', session('back_url'));
	}
	
	public function recommendAction($book_id = 0){
		try {
			$result = $this->cookBookService()->infoRecommend($book_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	public function popularAction($book_id = 0){
		try {
			$result = $this->cookBookService()->infoPopular($book_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	public function openAction($book_id = 0){
		try {
			$result = $this->cookBookService()->infoOpen($book_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function publicAssign() {
		//分类
		$this->assign('catlist', $this->cookCatService()->catTreeList());
		
		//标签
		$label_list = $this->cookLabelService()->getAllList();
		$this->assign('label_list', $label_list);
		
		//属性
		$cook_config = $this->configService()->getConfigs('cook');
		$this->assign('cook_config', $cook_config);
		
		//难度
		$this->assign('difficulty_list', Status::$cookDifficultyList);
		
		//人数
		$this->assign('number_list', Status::$cookNumberList);
		
		//准备时间
		$this->assign('preparetime_list', Status::$cookPrepareTimeList);
		
		//烹饪时间
		$this->assign('cooktime_list', Status::$cookTimeList);
	}
	
	private function cookBookService(){
		return D('CookBook', 'Service');
	}
	
	private function cookCatService(){
		return D('CookCat', 'Service');
	}
	
	private function cookLabelService(){
		return D('CookLabel', 'Service');
	}
}