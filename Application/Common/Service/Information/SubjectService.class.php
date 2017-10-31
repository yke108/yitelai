<?php
namespace Common\Service\Information;

class SubjectService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->subjectDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$subjectDao = $this->subjectDao();
		if (!$subjectDao->create($data)){
			 throw new \Exception($subjectDao->getError());
		}
		if ($params['subject_id'] > 0){
			$result = $subjectDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $subjectDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->subjectDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->subjectDao()->where(array('subject_id'=>$info['subject_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->subjectDao()->where(array('subject_id'=>$info['subject_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
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
		
		$subjectDao = $this->subjectDao();
		$count = $subjectDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order DESC, subject_id DESC' : $params['orderby'];
			$list = $subjectDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = array();
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		$subjectDao = $this->subjectDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $subjectDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			//来源
			$sources = $this->newsSourceDao()->searchFieldRecords();
			//作者
			$authors = $this->newsAuthorDao()->searchFieldRecords();
			
			foreach ($list as $k => $v) {
				//地区
				$list[$k]['region_name'] = $this->regionDao()->getProvinceCity($v['region_code']);
				//来源
				$list[$k]['source_name'] = $sources[$v['source_id']];
				//作者
				$list[$k]['author_img'] = $authors[$v['author_id']]['author_img'];
				$list[$k]['author_name'] = $authors[$v['author_id']]['author_name'];
				//时间
				$add_time = round((NOW_TIME - $v['add_time']) / 3600);
				$list[$k]['date_time'] = $add_time > 24 ? date('Y-m-d H:i', $v['add_time']) : $add_time.'小时前';
			}
		}
		
		return $list;
	}
	
	private function subjectDao(){
		return D('Common/Information/Subject/Info');
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
}//end HelpService!甜品