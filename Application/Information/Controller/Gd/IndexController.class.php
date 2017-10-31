<?php
namespace Information\Controller\Gd;
use Information\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'gd',
			'ac'=>'gd_index_index',
		);
		$this->sbset($set);
		
		$p_list = $this->adService()->positionAllList();
		$this->assign('p_list', $p_list);
    }
	
    public function indexAction($id = ''){
		$get = I('get.');
		$this->assign('get', $get);
		
		if(!empty($get['position_code'])){
			$map['position_code'] = $get['position_code'];
		}
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>$map
		);
		$datas = $this->adService()->infoPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->assign('t_now', NOW_TIME);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$model = $this->ddata();
			$res = $model->add();
			if($res !== false){
				$this->success('添加成功', U('index'));
			} else {
				$this->error('添加失败');
			}
		}
		$this->display('edit');
	}
	
	public function editAction($ad_id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $ad_id);
		if(IS_POST){
			$model = $this->ddata($ad_id);
			$res = $model->save();
			if($res !== false){
				$this->success('保存成功', U('index'));
			} else {
				$this->error('保存失败');
			}
		}
		$info['start_time'] = date('Y-m-d', $info['start_time']);
		$info['end_time'] = date('Y-m-d', $info['end_time']);
		$this->assign('info', $info);
		$this->display();
	}
	
    public function delAction($ad_id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $ad_id);
		$res = M('AdInfo')->where($gmap)->delete();
		if($res < 1){
			$this->error('删除失败');
		}
		$this->success('删除成功');
    }
	
	private function existChk(&$gmap, &$info, $ad_id = 0){
		$gmap = array(
			'ad_id'=>$ad_id,
		);
		$info = M('AdInfo')->where($gmap)->find();
		if(empty($info)){
			$this->error('内容不存在');
		}
	}
	
	private function ddata($ad_id = 0){
		$rules = array(
		    	array('position_code','require','未指定广告位', 1),
				array('ad_picture','require','广告图片不能为空', 1),
				array('start_time', 'require', '开始时间不能为空', 1),
				array('end_time', 'require', '结束时间不能为空', 1),
		);
	    
		$post = I('post.');
		
		$data = array(
				'position_code'=>trim($post['position_code']),
				//'ad_type'=>trim($post['ad_type']),
				//'ad_link'=>trim($post['ad_link']),
				'ad_picture'=>$post['ad_picture'],
				'ad_value'=>trim($post['ad_value']),
				'ad_name'=>trim($post['ad_name']),
				'ad_desc'=>trim($post['ad_desc']),
				'ad_time'=>strtotime($post['ad_time']),
				//'cat_id'=>trim($post['cat_id']?$post['cat_id']:0),
				'sort_order'=>intval($post['sort_order']),
				'enabled'=>intval($post['enabled']) > 0 ? 1 : 0,
				'start_time'=>strtotime(trim($post['start_time'])),
				'end_time'=>strtotime(trim($post['end_time'])),
				'distributor_id'=>$this->org_id
		);
	    
		if($ad_id > 0){
			$data['ad_id'] = $ad_id;
		}
		
		$model = M("AdInfo");
		if (!$model->validate($rules)->create($data)){
			$this->error($model->getError());
		}
		return $model;
	}
	
	private function adService() {
		return D('Information\Ad', 'Service');
	}
}