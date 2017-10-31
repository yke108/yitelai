<?php
namespace Information\Controller\Video;
use Information\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'news',
			'ac'=>'video_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//分类
    	$params = array('map'=>array('type'=>1));
    	$this->assign('all_cat_list', $this->newsService()->catOptionList($params, $get['cat_id']));
    	
    	//新闻列表
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type'=>1,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	$datas = $this->newsService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = $_POST;
			$post['type'] = 1;
			try {
				$result = $this->newsService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//分类
		$map = array('type'=>1);
		$this->assign('catlist', $this->newsService()->catTreeList($map));
		
		//地区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//来源
		$this->assign('source_list', $this->newsSourceService()->infoAllList());
		
		//作者
		$this->assign('author_list', $this->newsAuthorService()->infoAllList());
		
		//类型
		$this->assign('type_list', Status::$newsTypeShowList);
		
		$this->display('edit');
	}
	
	public function editAction($news_id = 0){
		$info = $this->newsService()->getInfo($news_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = $_POST;
			$post['type'] = 1;
			try {
				$result = $this->newsService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//分类
		$map = array('type'=>1);
		$this->assign('catlist', $this->newsService()->catTreeList($map));
		
		//地区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//来源
		$this->assign('source_list', $this->newsSourceService()->infoAllList());
		
		//作者
		$this->assign('author_list', $this->newsAuthorService()->infoAllList());
		
		//类型
		$this->assign('type_list', Status::$newsTypeShowList);
		
		$this->display();
	}
	
	public function delAction($news_id = 0){
		try {
			$result = $this->newsService()->infoDelete($news_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($news_id = 0){
		try {
			$result = $this->newsService()->infoRecommend($news_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($news_id = 0){
		try {
			$result = $this->newsService()->infoOpen($news_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function newsService(){
		return D('Information\News', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function newsSourceService(){
		return D('Information\NewsSource', 'Service');
	}
	
	private function newsAuthorService(){
		return D('Information\NewsAuthor', 'Service');
	}
}