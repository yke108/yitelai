<?php
namespace Common\Service\Information;
use Common\Basic\Status;
use Common\Basic\Genre;

class NewsService{
	// Cat
	public function getCat($id){
		if ($id < 1) return false;
		return $this->newsCatDao()->getRecord($id);
	}
	
	public function getCatInfo($map){
		return $this->newsCatDao()->findRecord($map);
	}
	
	public function catCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsCatDao = $this->newsCatDao();
		if (!$newsCatDao->create($data)){
			 throw new \Exception($newsCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $newsCatDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsCatDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function catDelete($cat_id){
		//有文章的分类不能删除
		$map = array('cat_id'=>$cat_id);
		$news_info = $this->newsInfoDao()->findRecord($map);
		if (!empty($news_info)) throw new \Exception('请先删除分类下的文章');
		
		$result = $this->newsCatDao()->delRecord($cat_id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function catDefault($cat_id){
		$info = $this->getCat($cat_id);
		$is_default = $info['is_default'] == 0 ? 1 : 0;
		$result = $this->newsCatDao()->where(array('cat_id'=>$info['cat_id']))->save(array('is_default'=>$is_default));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['type']) {
			$map['type'] = $params['type'];
		}
		
		$newsCatDao = $this->newsCatDao();
		$count = $newsCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id ASC' : $params['orderby'];
			$list = $newsCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function catAllList($map, $orderby = 'sort_order ASC, cat_id ASC'){
		return $this->newsCatDao()->searchAllRecords($map, $orderby);
	}
	
	public function catTreeList($map, $orderby = 'sort_order ASC, cat_id ASC'){
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		return genTree($list, 'cat_id', 'parent_id');
	}
	
	public function catTrList($params){
		$map = $params['map'] ? $params['map'] : array();
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id ASC' : $params['orderby'];
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
		
		$result = array();
		foreach ($list as $vo) {
			$url = U('default',array('cat_id'=>$vo['cat_id']));
			if ($vo['is_default'] == 1) {
				$is_default = '<a class="cs_ajax_link label label-success" href="'.$url.'">是</a>';
			}else {
				$is_default = '<a class="cs_ajax_link label label-danger" href="'.$url.'">否</a>';
			}
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'cat_name'=>$vo['cat_name'],
					'is_default'=>$is_default,
					'sort_order'=>$vo['sort_order'],
					'edit_url'=>U('edit', array('cat_id'=>$vo[cat_id])),
					'del_url'=>U('del', array('cat_id'=>$vo[cat_id])),
			);
		}
		
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<tr>
					<td>\$spacer\$cat_name</td>
					<td>\$sort_order</td>
				    <td>\$is_default</td>
					<td>
						<a href='\$edit_url' class='cs_ajax_link hy_show_modal'>编辑</a>
						<a href='\$del_url' class='cs_del_confirm' cs_tip='确定删除？'>删除</a>
					</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		
		return $categorys;
	}
	
	public function catOptionList($params, $select_id){
		$map = $params['map'] ? $params['map'] : array();
		$orderby = empty($params['orderby']) ? 'sort_order ASC, cat_id ASC' : $params['orderby'];
		$list = $this->newsCatDao()->searchAllRecords($map, $orderby);
		$list = $this->outputForList($list);
	
		$result = array();
		foreach ($list as $vo) {
			$result[$vo['cat_id']] = array(
					'id'=>$vo['cat_id'],
					'parentid'=>$vo['parent_id'],
					'name'=>$vo['cat_name'],
			);
		}
	
		$tree = new \Org\Util\Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$tree->init($result);
		$str = "<option value=\$id \$selected>\$spacer\$name</option>";
		$categorys = $tree->get_tree(0, $str, $select_id);
	
		return $categorys;
	}
	
	//获取分类的所有子类（包含自己）
	public function catChilds($cat_id){
		$use_category = false;
		$cat_list = $this->newsCatDao()->getField('cat_id, parent_id');
		$cats = array();
		foreach($cat_list as $ck => $cv){
			if($ck == $cat_id) {
				$use_category = true;
			}
			$cats[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($cat_id, $cats, $clist);
		}
		return $clist;
	}
	
	private function newsCatDao(){
		//调用model
		return D('Common/Information/News/Cat');
	}

	//news_info
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->newsInfoDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->newsInfoDao()->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		$data['pictures'] = trim($params['pictures'], ',');
		//$data['content'] = $data['path'] ? $data['path'] : $data['content'];
		if ($data['path']) {
			$path = $data['path'];
			$path = preg_replace('/\"/', '\'', $path);
			$path = str_replace('width=\'640\'', 'width=\'100%\'', $path);
			$path = str_replace('height=\'498\'', 'height=\'100%\'', $path);
			$path = str_replace('width=510', 'width=100%', $path);
			$path = str_replace('height=498', 'height=100%', $path);
			$data['content'] = $path;
		}
		
		$newsInfoDao = $this->newsInfoDao();
		if (!$newsInfoDao->create($data)){
			 throw new \Exception($newsInfoDao->getError());
		}
		if ($params['news_id'] > 0){
			$result = $newsInfoDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsInfoDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function read($params){
		$info = $this->getInfo($params['news_id']);
		if (empty($info)) throw new \Exception('内容不存在');
		
		M()->startTrans();
		
		$result = $this->newsInfoDao()->where(array('news_id'=>$params['news_id']))->setInc('read_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		if ($params['user_id']) {
			$data = array(
					'user_id'=>$params['user_id'],
					'news_id'=>$params['news_id'],
					'start_time'=>NOW_TIME,
			);
			$result = $this->newsReadDao()->add($data);
			if ($result === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		//浏览记录
		$data = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$info['news_id'],
				'collect_type'=>Genre::CollectTypeNews,
				'system'=>$params['system'],
				'browser'=>$params['browser'],
				'version'=>$params['version'],
				'add_time'=>NOW_TIME,
		);
		$result = $this->collectDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	public function collect($params){
		$news_info = $this->getInfo($params['news_id']);
		if (empty($news_info)) throw new \Exception('文章不存在');
		
		$newsCollectDao = $this->newsCollectDao();
		
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['news_id'],
				'collect_type'=>Genre::CollectTypeNews,
		);
		$collect_info = $newsCollectDao->findRecord($map);
		
		$newsCollectDao->startTrans();
		
		if ($collect_info) {
			$result = $newsCollectDao->where($map)->delete();
			if ($result === false) {
				$newsCollectDao->rollback();
				throw new \Exception('取消失败');
			}
			
			//统计收藏数
			$result = $this->newsInfoDao()->where(array('news_id'=>$params['news_id']))->setDec('collect_count');
			if ($result === false) {
				$newsCollectDao->rollback();
				throw new \Exception('取消失败');
			}
		}else {
			$data = array(
					'user_id'=>$params['user_id'],
					'id_value'=>$params['news_id'],
					'collect_type'=>Genre::CollectTypeNews,
					'add_time'=>NOW_TIME,
			);
			$result = $newsCollectDao->add($data);
			if ($result === false) {
				$newsCollectDao->rollback();
				throw new \Exception('收藏失败');
			}
			
			//统计收藏数
			$result = $this->newsInfoDao()->where(array('news_id'=>$params['news_id']))->setInc('collect_count');
			if ($result === false) {
				$newsCollectDao->rollback();
				throw new \Exception('收藏失败');
			}
		}
		
		$newsCollectDao->commit();
	}
	
	public function infoDelete($news_id){
		$result = $this->newsInfoDao()->delRecord($news_id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoOpen($news_id){
		$info = $this->getInfo($news_id);
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoRecommend($news_id){
		$info = $this->getInfo($news_id);
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoTop($news_id){
		$info = $this->getInfo($news_id);
		$is_top = $info['is_top'] == 0 ? 1 : 0;
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->save(array('is_top'=>$is_top));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['cat_id'])) {
			$clist = $this->catChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		if (isset($params['type'])) {
			$map['type'] = $params['type'];
		}
		if (isset($params['type_show'])) {
			$map['type_show'] = $params['type_show'];
		}
		
		$newsInfoDao = $this->newsInfoDao();
		$count = $newsInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'is_top DESC, sort_order DESC, news_id DESC' : $params['orderby'];
			$list = $newsInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		$newsInfoDao = $this->newsInfoDao();
		$orderby = empty($params['orderby']) ? 'sort_order DESC, news_id DESC' : $params['orderby'];
		return $newsInfoDao->searchAllRecords($map, $orderby);
	}
	
	public function share($params){
		if (empty($params['user_id']) || empty($params['news_id'])) throw new \Exception('缺少参数');
		
		$info = $this->getInfo($params['news_id']);
		if (empty($info)) throw new \Exception('内容不存在');
		
		M()->startTrans();
		
		//分享记录
		$data = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$info['news_id'],
				'collect_type'=>Genre::CollectTypeNews,
				'add_time'=>NOW_TIME,
		);
		$result = $this->collectDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		//分享统计
		$result = $this->newsInfoDao()->where(array('news_id'=>$info['news_id']))->setInc('share_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		M()->commit();
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$cat_ids = array();
			foreach ($list as $k => $v) {
				$cat_ids[] = $v['cat_id'];
			}
			//分类
			$map['cat_id'] = array('in', $cat_ids);
			$cats = $this->newsCatDao()->searchFieldRecords($map);
			//来源
			$sources = $this->newsSourceDao()->searchFieldRecords();
			//作者
			$authors = $this->newsAuthorDao()->searchFieldRecords();
			
			foreach ($list as $k => $v) {
				//图片
				$list[$k]['pictures'] = $v['pictures'] ? explode(',', $v['pictures']) : array();
				//分类
				$list[$k]['cat_name'] = $cats[$v['cat_id']];
				//地区
				$list[$k]['region_name'] = $this->regionDao()->getProvinceCity($v['region_code']);
				//来源
				$list[$k]['source_name'] = $sources[$v['source_id']];
				//作者
				$list[$k]['author_img'] = $authors[$v['author_id']]['author_img'];
				$list[$k]['author_name'] = $authors[$v['author_id']]['author_name'];
				//时间
				$add_time = round((NOW_TIME - $v['add_time']) / 3600);
				if ($add_time < 1) {
					$add_time = round((NOW_TIME - $v['add_time']) / 60);
					$list[$k]['date_time'] = $add_time.'分钟前';
				}elseif ($add_time < 24) {
					$list[$k]['date_time'] = $add_time.'小时前';
				}else {
					$list[$k]['date_time'] = date('Y-m-d H:i', $v['add_time']);
				}
				//展示方式
				$list[$k]['type_show_name'] = Status::$newsTypeShowList[$v['type_show']];
				//视频地址处理
				if ($v['type'] == Status::NewsTypeVideo) {
					$list[$k]['content_type'] = (strpos($v['content'], '://') > 0) ? 1 : 0;
				}
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			$cat = $this->newsCatDao()->find($info['cat_id']);
			$source = $this->newsSourceDao()->find($info['source_id']);
			$author = $this->newsAuthorDao()->find($info['author_id']);
			
			$info['pictures_arr'] = $info['pictures'] ? explode(',', $info['pictures']) : array();
			$info['cat_name'] = $cat['cat_name'];
			$info['region_name'] = $this->regionDao()->getProvinceCity($info['region_code']);
			$info['source_name'] = $source['source_name'];
			$info['author_img'] = $author['author_img'];
			$info['author_name'] = $author['author_name'];
			if ($info['type'] == Status::NewsTypeVideo) {
				$info['content'] = preg_replace('/\"/', '\'', $info['content']);
				$info['content_type'] = (strpos($info['content'], '://') > 0) ? 1 : 0;
			}
		}
	
		return $info;
	}
	
	private function newsInfoDao(){
		return D('Common/Information/News/Info');
	}
	
	private function newsSourceDao(){
		return D('Common/Information/News/Source');
	}
	
	private function newsAuthorDao(){
		return D('Common/Information/News/Author');
	}
	
	private function regionDao(){
		return D('Region');
	}
	
	private function newsCollectDao(){
		return D('Common/Information/News/Collect');
	}
	
	private function newsReadDao(){
		return D('Common/Information/News/Read');
	}
	
	private function collectDao(){
		return D('Collect');
	}
}//end HelpService!甜品