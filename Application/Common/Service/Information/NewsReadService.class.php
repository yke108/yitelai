<?php
namespace Common\Service\Information;

class NewsReadService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->newsReadDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$newsReadDao = $this->newsReadDao();
		if (!$newsReadDao->create($data)){
			 throw new \Exception($newsReadDao->getError());
		}
		if ($params['read_id'] > 0){
			$result = $newsReadDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $newsReadDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->newsReadDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (isset($params['status'])) {
			$map['status'] = $params['status'];
		}
		
		$newsReadDao = $this->newsReadDao();
		$count = $newsReadDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'read_id desc' : $params['orderby'];
			$list = $newsReadDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = $params['map'];
		$newsReadDao = $this->newsReadDao();
		$orderby = empty($params['orderby']) ? 'read_id DESC' : $params['orderby'];
		return $newsReadDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$news_ids = array();
			foreach ($list as $v) {
				$news_ids[] = $v['news_id'];
			}
			//新闻
			$map['news_id'] = array('in', $news_ids);
			$news = $this->newsInfoDao()->searchFieldRecords($map);
			//来源
			$sources = $this->newsSourceDao()->searchFieldRecords();
			//作者
			$authors = $this->newsAuthorDao()->searchFieldRecords();
				
			foreach ($list as $k => $v) {
				//新闻
				$list[$k]['pictures'] = $news[$v['news_id']]['pictures'] ? explode(',', $news[$v['news_id']]['pictures']) : array();
				$list[$k]['picture'] = $news[$v['news_id']]['picture'];
				$list[$k]['title'] = $news[$v['news_id']]['title'];
				$list[$k]['type'] = $news[$v['news_id']]['type'];
				$list[$k]['type_show'] = $news[$v['news_id']]['type_show'];
				$list[$k]['content'] = $news[$v['news_id']]['content'];
				$list[$k]['read_count'] = $news[$v['news_id']]['read_count'];
				$list[$k]['comment_count'] = $news[$v['news_id']]['comment_count'];
				//来源
				$list[$k]['source_name'] = $sources[$news[$v['news_id']]['source_id']];
				//作者
				$list[$k]['author_img'] = $authors[$news[$v['news_id']]['author_id']]['author_img'];
				$list[$k]['author_name'] = $authors[$news[$v['news_id']]['author_id']]['author_name'];
			
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
			}
		}
		
		return $list;
	}
	
	private function newsReadDao(){
		return D('Common/Information/News/Read');
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
}//end HelpService!甜品