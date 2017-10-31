<?php
namespace Main\Controller\Cook;
use Main\Controller\MainController;
use Common\Basic\Status;
use Common\Basic\Pager;

class IndexController extends MainController {
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	//热门栏目推荐
    	$label_list = $this->cookLabelService()->getAllList();
    	$this->assign('label_list', $label_list);
    	
    	//今日早餐推荐
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>12,
    			'is_recommend'=>1,
    	);
    	$datas = $this->cookBookService()->infoPagerList($params);
    	$this->assign('recommend_list', $datas['list']);
    	
    	//今日最受欢迎菜谱
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>12,
    			'is_popular'=>1,
    	);
    	$datas = $this->cookBookService()->infoPagerList($params);
    	$this->assign('today_list', $datas['list']);
    	
    	//排行榜
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'orderby'=>'read_count desc',
    	);
    	$datas = $this->cookBookService()->infoPagerList($params);
    	$this->assign('top_list', $datas['list']);
    	
    	$this->display();
    }
    
    public function listAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>16,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['label_id'])) {
    		$params['label_id'] = $get['label_id'];
    	}
    	$datas = $this->cookBookService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $params['pagesize']);
    	$this->assign('pager', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function infoAction($id = 0){
    	$info = $this->cookBookService()->getInfo($id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	//统计浏览数
    	$this->cookBookService()->readCount($id);
    	
    	$this->assign('info', $info);
    	
    	//评论列表
    	$params = array(
    			'page'=>intval(I('p')),
    			'pagesize'=>20,
    			'book_id'=>$info['book_id'],
    	);
    	$result = $this->cookCommentService()->infoPagerList($params);
    	$this->assign('comment_list', $result['list']);
    	$pager = new Pager($result['count'], $params['pagesize']);
    	$this->assign('pager', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function addAction(){
    	if (empty($this->user)) {
    		$url = DK_DOMAIN.U('index/site/login');
    		header('Location:'.$url);
    	}
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		
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
    		
    		try {
    			$result = $this->cookBookService()->infoCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('发布成功', U('index'));
    	}
    	
    	$this->publicAssign();
    	
    	$this->display();
    }
    
    private function publicAssign() {
    	//分类
    	//$this->assign('catlist', $this->cookCatService()->catTreeList());
    
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
    
    public function commentAction($book_id = 0) {
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	try {
    		$result = $this->cookCommentService()->infoCreateOrModify($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('评论成功');
    }
    
    public function likeAction($comment_id = 0) {
    	if (empty($this->user)) {
    		$this->error('请先登录');
    	}
    	try {
    		$result = $this->cookCommentService()->infoLike($comment_id);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('点赞成功');
    }
    
    private function cookBookService(){
    	return D('CookBook', 'Service');
    }
    
    private function cookLabelService(){
    	return D('CookLabel', 'Service');
    }
    
    private function cookCommentService(){
    	return D('CookComment', 'Service');
    }
}