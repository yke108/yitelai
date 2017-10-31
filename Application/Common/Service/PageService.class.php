<?php
namespace Common\Service;
use Common\Basic\Status;
class PageService{
	//page_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->pageInfoDao()->getRecord($id);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('page_title', 'require', '标题是必须的'),
		);
		//参数
		$data = array(
				'page_picture'=>trim($params['page_picture']),
				'page_title'=>trim($params['page_title']),
				'page_intro'=>trim($params['page_intro']),
				'page_content'=>trim($params['page_content']),
				//'page_type'=>trim($params['page_type']),
				'add_time'=>time(),
				'distributor_id'=>$params['distributor_id'],
				//'sort_order'=>$params['sort_order'],		
		);
		
		if($params['page_id'] > 0){
			$data['page_id'] = $params['page_id'];
		}
		$pageInfoDao = $this->pageInfoDao();
		if (!$pageInfoDao->validate($rules)->create($data)){
			 throw new \Exception($pageInfoDao->getError());
		}
		if ($params['page_id'] > 0){
			$result = $pageInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$data['page_type'] = trim($params['page_type']);
			$result = $pageInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$info = $this->getInfo($id);
		if(empty($info)) throw new \Exception('内容不存在');
		if(in_array($info['page_type'], array(Status::PageTypeSystem, Status::PageTypeMerchant))) throw new \Exception('不能删除该条信息');
		$result = $this->pageInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		//搜索
		!empty($params['keyword']) && $map['page_title'] = array('like','%'.$params['keyword'].'%');
		
		$pageInfoDao = $this->pageInfoDao();
		$count = $pageInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'page_id desc' : $params['orderby'];
			$list = $pageInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	public function infoAllList($map){
		return $this->pageInfoDao()->getFieldRecords($map);
	}
	//调用model
	private function pageInfoDao(){
		return D('Common/Page/PageInfo');
	}
}//end HelpService!