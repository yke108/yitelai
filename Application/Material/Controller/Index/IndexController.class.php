<?php
namespace Material\Controller\Index;
use Material\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Genre;

class IndexController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$cat_list = $this->materialCatService()->getAllList();
		$this->assign('cat_list', $cat_list['list']);
    }
	
    public function indexAction(){
    	//品牌后台地址
    	$distributor_url = DK_DOMAIN.'/distributor';
    	$this->assign('distributor_url', $distributor_url);
    	
    	//标签
    	$label = $this->materialLabelService()->getAllList();
    	$this->assign('label_list', $label['list']);
    	
    	//热门标签
    	$map = array('is_recommend'=>1);
    	$label_recommend_list = $this->materialLabelService()->selectAllList($map);
    	$this->assign('label_recommend_list', $label_recommend_list);
    	
    	//大V设计师
    	$params = array(
    			'page'=>1,
    			'pagesize'=>2,
    			'orderby'=>'down_count DESC',
    			'is_authentication'=>1
    	);
    	$datas = $this->designerService()->infoPagerList($params);
    	$this->assign('designer_v_list', $datas['list']);
    	
    	//设计师排行榜
    	$params = array(
    			'page'=>1,
    			'pagesize'=>2,
    			'orderby'=>'down_count DESC',
    			'is_authentication'=>0
    	);
    	$datas = $this->designerService()->infoPagerList($params);
    	$this->assign('designer_list', $datas['list']);
    	
    	//下载排行榜
    	$params = array(
    			'page'=>1,
    			'pagesize'=>2,
    			'is_show'=>1,
    			'orderby'=>'down_count DESC',
    	);
    	$datas = $this->materialService()->getPagerList($params);
    	$this->assign('down_list', $datas['list']);
    	
    	//设计师统计
    	$designer_count = $this->designerService()->infoCount();
    	$this->assign('designer_count', $designer_count);
    	
    	//门店设计师
    	
    	//素材总数
    	$material_count = $this->materialService()->getCount();
    	$this->assign('material_count', $material_count);
    	
    	//一周新增
    	$map = array('_string'=>"DATE(FROM_UNIXTIME(add_time)) > DATE_SUB(CURDATE(), INTERVAL 1 WEEK)");
    	$material_week_count = $this->materialService()->getCount($map);
    	$this->assign('material_week_count', $material_week_count);
    	
    	//总下载数
    	$down_count = $this->materialService()->getDownCount();
    	$this->assign('down_count', $down_count);
    	
    	//昨天下载
    	$down_yesterday_count = $this->materialService()->getYesterdayDownCount();
    	$this->assign('down_yesterday_count', $down_yesterday_count);
    	
    	//热门专题
    	$params = array(
    			'page'=>1,
    			'pagesize'=>4,
    			'is_recommend'=>1,
    			'is_show'=>1,
    	);
    	$datas = $this->materialCatService()->getPagerList($params);
    	$this->assign('cat_recommend_list', $datas['list']);
    	
    	$params = array(
    			'page'=>1,
    			'pagesize'=>5,
    			'is_recommend'=>1,
    	);
    	$datas = $this->materialService()->getPagerList($params);
    	$this->assign('recommend_list', $datas['list']);
    	
    	//楼层
    	$params = array(
    			'is_recommend'=>1,
    			'is_show'=>1,
    	);
    	$datas = $this->materialCatService()->getAllList($map);
    	foreach ($datas['list'] as $k => $v) {
    		$clist = $this->materialCatService()->getCatChilds($v['cat_id']);
    		$clist = implode(',', $clist);
    		$params = array(
    				'page'=>1,
    				'pagesize'=>5,
    				'is_show'=>1,
    				'map'=>array('_string'=>"cat_id IN ($clist)"),
    				'user_id'=>$this->user['user_id'],
    		);
    		$material_list = $this->materialService()->getPagerList($params);
    		$datas['list'][$k]['material_list'] = $material_list['list'];
    	}
    	$this->assign('floor_list', $datas['list']);
    	
    	//用户统计
    	$user_count = $this->userService()->getCount();
    	$this->assign('user_count', $user_count);
    	
    	$params = array(
    			'_page'=>1,
    			'_size'=>5,
    			'_map'=>array('user_img'=>array('neq', ''))
    	);
    	$datas = $this->userService()->getList($params);
    	$this->assign('user_list', $datas['list']);
    	
    	$this->assign('page_title', '图库首页');
		$this->display();
    }
	
    public function listAction(){
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
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>30,
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
    	
    	//属性筛选
    	$filter_attr_str = isset($get['filter_attr']) ? htmlspecialchars(trim($get['filter_attr'])) : '0';
    	$filter_attr_str = trim(urldecode($filter_attr_str));
    	$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
    	$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
    	$ext = '';
    	if (!empty($filter_attr))
    	{
    		$material_spec = M('material_spec')->field('spec_id')->where(array('type_id'=>$cat['type_id']))->select();
    		foreach ($material_spec as $v) {
    			$cat_filter_attr[] = $v['spec_id'];
    		}
    		
    		$ext_sql = "SELECT DISTINCT(b.material_id) FROM __MATERIAL_SPEC_VALUES__ AS a, __MATERIAL_SPEC_VALUES__ AS b " .  "WHERE ";
    		$ext_group_material = array();
    		foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
    		{
    			if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
    			{
    				$sql = $ext_sql . "b.spec_value = a.spec_value AND b.spec_id = " . $cat_filter_attr[$k] ." AND a.material_spec_value_id = " . $v;
    				$ext_group_material_tmp = M()->query($sql);
    				$ext_group_material = array();
    				foreach ($ext_group_material_tmp as $v) {
    					$ext_group_material[] = $v['material_id'];
    				}
    				$ext .= ' AND ' . $this->db_create_in($ext_group_material, 'material_id');
    			}
    		}
    		
    		if ($ext) {
    			$map['_string'] = '1'.$ext;
    			$params['map'] = $map;
    		}
    	}
    	
    	if ($get['sort_order']) {
    		switch ($get['sort_order']) {
    			case 'down': $params['orderby'] = 'down_count DESC'; break;
    			case 'collect': $params['orderby'] = 'collect_count DESC'; break;
    		}
    	}
    	$datas = $this->materialService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $params['pagesize']);
    	$this->assign('pages', $pager->show_pc());
    	
    	//属性筛选
    	if ($cat['cat_id'] > 0) {
    		$all_attr_list = $this->materialService()->get_filter_attr($cat['cat_id'], $get);
    		$this->assign('filter_attr_list',  $all_attr_list);
    	}
    	
    	$this->assign('page_title', '图库列表');
    	$this->display();
    }
    
	public function infoAction($id = 0){
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
		
		//是否收藏
		$params = array(
				'user_id'=>$this->user['user_id'],
				'id_value'=>$info['material_id'],
				'collect_type'=>Genre::CollectTypeMaterial,
		);
		$collect = $this->collectService()->findInfo($params);
		$this->assign('collect', $collect);
		
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
		
		//设计师图库
		$params = array(
				'pagesize'=>3,
				'designer_id'=>$designer_info['designer_id'],
				'is_show'=>1,
		);
		$datas = $this->materialService()->getPagerList($params);
		$this->assign('designer_material_list', $datas['list']);
		
		$this->assign('page_title', '图库详情');
		$this->display();
	}
	
	public function previewAction($id = 0){
		$info = $this->materialService()->getInfo($id);
		$this->assign('info', $info);
		
		$this->assign('page_title', '图库详情');
		$this->display();
	}
	
	public function collectAction($id = 0){
		$params = array(
				'material_id'=>$id,
				'user_id'=>$this->user['user_id'],
		);
		try {
			$result = $this->materialService()->collect($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('收藏成功', U('uncollect', array('id'=>$id)), array('collect_count'=>$result['collect_count'], 'collect_text'=>'已收藏'));
	}
	
	public function uncollectAction($id = 0){
		$params = array(
				'material_id'=>$id,
				'user_id'=>$this->user['user_id'],
		);
		try {
			$result = $this->materialService()->unCollect($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('取消成功', U('collect', array('id'=>$id)), array('collect_count'=>$result['collect_count'], 'collect_text'=>'收藏'));
	}
	
	public function downcheckAction($id = 0){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', $this->login_url);
		}
		
		$info = $this->materialService()->getInfo($id);
		/* if(empty($info['upload_path'])){
			$this->error('文件不存在');
		} */
		
		//判断积分是否足够
		if ($info['down_points'] > $this->user['pay_points']) {
			$this->error('积分不够');
		}
		
		//$this->success('正在转向下载', DK_DOMAIN.U('download', array('id'=>$info['material_id'])), array('down_count'=>$info['down_count']+1));
		$this->assign('file_list', $info['file_list']);
		$html = $this->renderPartial('file_list');
		$data = array(
				'html'=>$html,
				//'down_count'=>$info['down_count']+1,
		);
		$this->success('正在转向下载', DK_DOMAIN.U('download', array('id'=>$info['material_id'])), $data);
	}
	
	/* public function downloadAction($id = 0){
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
	} */
	
	public function downloadAction($id = 0){
		header("Content-type:text/html;charset=utf-8");
		
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', $this->login_url);
		}
		
		$file = $this->materialService()->getFileInfo($id);
		
		$info = $this->materialService()->getInfo($file['material_id']);
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
		$file = UPLOAD_PATH.$file['upload_path'];
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
			//exit('文件不存在！');
			$this->error('文件不存在');
		}
		
		$data = array(
				'down_count'=>$info['down_count']+1,
		);
		$this->success('正在转向下载', '', $data);
	}
	
	private function db_create_in($item_list, $field_name = '')
	{
		if (empty($item_list))
		{
			return $field_name . " IN ('') ";
		}
		else
		{
			if (!is_array($item_list))
			{
				$item_list = explode(',', $item_list);
			}
			$item_list = array_unique($item_list);
			$item_list_tmp = '';
			foreach ($item_list AS $item)
			{
				if ($item !== '')
				{
					$item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
				}
			}
			if (empty($item_list_tmp))
			{
				return $field_name . " IN ('') ";
			}
			else
			{
				return $field_name . ' IN (' . $item_list_tmp . ') ';
			}
		}
	}
	
	private function collectService(){
		return D('Collect', 'Service');
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