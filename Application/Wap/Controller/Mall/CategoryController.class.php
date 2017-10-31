<?php
namespace Wap\Controller\Mall;
use Wap\Controller\WapController;
use Common\Basic\Pager;

class CategoryController extends WapController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品分类
    	$map['is_show'] = 1;
    	$datas = $this->goodsCatService()->getAllList($map);
		$this->assign('cat_list', $datas['list']);
		
		//当前分类
		$cat = array();
		if ($get['cat_id'] > 0) {
			$cat = $this->goodsCatService()->getInfo($get['cat_id']);
		}
		if (empty($cat)) {
			$cat = current($datas['list']);
		}
		$this->assign('cat', $cat);
		
		//所有子类
		$childs = $this->goodsCatService()->getCatChilds($cat['cat_id']);
		$map['cat_id'] = array('in', $childs);
		$children = $this->goodsCatService()->getAllList($map);
		$this->assign('children', $children['list']);
		
		/* $list = array();
		foreach ($cat['children'] as $k => $v) {
			$list[$k]['cat_name'] = $v['cat_name'];
			$params = array(
					'page'=>1,
					'pagesize'=>6,
					'cat_id'=>$v['cat_id'],
					'map'=>array('a.product_num'=>array('gt', 0))
			);
			$datas = $this->distributorGoodsService()->getPagerList($params);
			$list[$k]['goods_list'] = $datas['list'];
		}
		$this->assign('list', $list); */
		
		$this->display();
    }
	
	public function groupbuyAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
		$p=$get['p'] < 1 ? 1 : $get['p'];
		$size=12;
		
		//获取团购商品列表
		$map=array('distributor_id'=>I('store_id'));
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map,'is_going'=>1);
		$team_result=$this->activityService()->teamPagerList($params);
		$team_list=$team_result['list'];
		
		foreach($team_list as $key=>$val){
			$distributor_id[]=$val['distributor_id'];
		}
		
		if(!empty($distributor_id)){
			$distributor_name=$this->distributorInfoService()->getFieldData(array('distributor_id'=>array('in',$distributor_id)),'distributor_id,distributor_name');
			foreach($team_list as $key=>$val){
				$team_list[$key]['distributor_name']=$distributor_name[$val['distributor_id']];
			}
		}
		
		$team_pager=new Pager($team_result['count'],$size);
		$team_pager->setConfig('header','');
		$this->assign('team_list',$team_list);
		$this->assign('team_page',$team_pager->show());
		
    	 
    	$this->display();
    }
	
	private function distributorInfoService(){
		return D('Distributor', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function goodsCatService(){
		return D('GoodsCat', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function shippingService(){
		return D('Shipping', 'Service');
	}
	
	private function activityService(){
		return D('Activity','Service');
	}
	
}