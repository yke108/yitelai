<?php
namespace Common\Service;

class MerchantBrandService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->merchantBrandDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->merchantBrandDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->merchantBrandDao()->create($data)){
			 throw new \Exception($this->merchantBrandDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['brand_id'] > 0){
			$result = $this->merchantBrandDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			//最多可添加10条品牌信息
			$brand_count = $this->merchantBrandDao()->where(array('user_id'=>$params['user_id']))->count();
			if ($brand_count > 10) throw new \Exception('最多可添加10条品牌信息');
			
			$brand_id = $this->merchantBrandDao()->add();
			if ($brand_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($map){
		$result = $this->merchantBrandDao()->where($map)->delete();
		if (!$result){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['keyword'])) {
			$map['distributor_name'] = array('like', '%'.$params['keyword'].'%');
		}
		
		$count = $this->merchantBrandDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'brand_id DESC' : $params['orderby'];
			$list = $this->merchantBrandDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map, $orderby){
		$orderby = empty($orderby) ? 'brand_id DESC' : $orderby;
		$list = $this->merchantBrandDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($brand_id){
		$info = $this->merchantBrandDao()->find($brand_id);
		if (empty($info)) throw new \Exception('数据不存在');
		$is_show = $info['is_show'] == 1 ? 0 : 1;
		$result = $this->merchantBrandDao()->where(array('brand_id'=>$brand_id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k=> $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			;
		}
		
		return $info;
	}
	
	private function merchantBrandDao(){
		return D('Common/Merchant/MerchantBrand');
	}
}//end HelpService!甜品