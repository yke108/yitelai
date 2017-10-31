<?php
namespace Common\Service;

class GoodsBrandService{
	protected $GoodsBrandDao;
	
	public function __construct(){
		$this->GoodsBrand = D('Common/Goods/GoodsBrand');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->GoodsBrand->getRecord($id);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		if (!$this->GoodsBrand->create($data)){
			 throw new \Exception($this->GoodsBrand->getError());
		}
		
		if ($params['brand_id'] > 0){
			$result = $this->GoodsBrand->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->GoodsBrand->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->GoodsBrand->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		if (isset($params['is_show'])) {
			$map['is_show'] = $params['is_show'];
		}
		$params['keyword'] && $map['brand_name'] = array('like', '%'.trim($params['keyword']).'%');
		
		$count = $this->GoodsBrand->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, brand_id DESC' : $params['orderby'];
			$list = $this->GoodsBrand->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array(), $orderby){
		return $this->GoodsBrand->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			//获取分类
			foreach ($list as $v) {
				if ($v['cat_id']) {
					$cat_ids[] = $v['cat_id'];
				}
			}
			$goods_cats = $this->goodsCatDao()->searchRecordsField($cat_ids);
			foreach ($list as $k => $v) {
				$list[$k]['cat_name'] = $goods_cats[$v['cat_id']];
			}
		}
		
		return $list;
	}
	
	public function setDisBrand($params){
		$distributor_info = $this->distributorInfoDao()->getRecord($params['distributor_id']);
		$brand_ids = $distributor_info['brand_ids'] ? explode(',', $distributor_info['brand_ids']) : array();
		
		//存在则删除，不存在则添加
		if (in_array($params['brand_id'], $brand_ids)) {
			foreach ($brand_ids as $k => $v) {
				if ($v == $params['brand_id']) {
					unset($brand_ids[$k]);
					break;
				}
			}
		}else {
			$brand_ids[] = $params['brand_id'];
		}
		$brand_ids = implode(',', $brand_ids);
		$brand_ids = ','.$brand_ids.',';
		$map = array('distributor_id'=>$distributor_info['distributor_id']);
		$result = $this->distributorInfoDao()->where($map)->save(array('brand_ids'=>$brand_ids));
		if($result === false) throw new \Exception('修改失败');
	}
	
	//设计师推荐首页
	public function isRecommend($brand_id){
		$info=$this->getInfo($brand_id);
		if(empty($info)){throw new \Exception('推荐品牌失败');}
		$data=array('brand_id'=>$brand_id,'is_recommend'=>($info['is_recommend']==1?0:1));
		
		$result=$this->GoodsBrand->save($data);
		
		if($result==false){
			throw new \Exception('推荐品牌失败');
		}
	}
	
	private function goodsCatDao() {
		return D('Common/Goods/GoodsCat');
	}
	
	private function distributorInfoDao() {
		return D('Common/Distributor/Info');
	}
}//end HelpService!甜品