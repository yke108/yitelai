<?php
namespace Common\Service\Information;
use Common\Basic\Genre;

class NewsCollectService{
	function getInfo($id) {
		return $this->newsCollectDao()->getRecord($id);
	}
	
	function findInfo($params) {
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['id_value'],
				'collect_type'=>$params['collect_type'],
		);
		return $this->newsCollectDao()->findRecord($map);
	}
	
	function delCollect($map) {
		$res = $this->newsCollectDao()->delRecord($map);
		if (!$res) {
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	function getPagerList($params) {
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		
		$count = $this->newsCollectDao()->searchRecordsCount($map);
		$list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$list = $this->newsCollectDao()->searchRecords($map, $orderBy, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$this->outPutForList($list),
				'count'=>$count,
		);
	}
	
	public function getAllList($map) {
		$list = $this->newsCollectDao()->searchAllRecords($map);
		return $this->outPutForList($list);
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			$news_ids = array();
			foreach ($list as $v) {
				$news_ids[] = $v['id_value'];
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
				$list[$k]['picture'] = $news[$v['id_value']]['picture'];
				$list[$k]['title'] = $news[$v['id_value']]['title'];
				$list[$k]['type_show'] = $news[$v['id_value']]['type_show'];
				$list[$k]['content'] = $news[$v['id_value']]['content'];
				$list[$k]['read_count'] = $news[$v['id_value']]['read_count'];
				$list[$k]['comment_count'] = $news[$v['id_value']]['comment_count'];
				//来源
				$list[$k]['source_name'] = $sources[$news[$v['id_value']]['source_id']];
				//作者
				$list[$k]['author_img'] = $authors[$news[$v['id_value']]['author_id']]['author_img'];
				$list[$k]['author_name'] = $authors[$news[$v['id_value']]['author_id']]['author_name'];
				
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
	
	private function newsCollectDao(){
		return D('Common/Information/News/Collect');
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
}