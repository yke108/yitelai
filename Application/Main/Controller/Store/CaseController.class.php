<?php
namespace Main\Controller\Store;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Basic\Pager;

class CaseController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$this->assign('page_title', '真实案例');
    }
	
    public function indexAction($store_id = 0){
    	//分销商
    	$distributor = $this->distributorService()->getInfo($store_id);
    	if (empty($distributor)) {
    		$this->error('店铺不存在');
    	}
    	$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    	$this->assign('distributor', $distributor);
    	
    	//微信端地址
    	$store_url = DK_DOMAIN.U('store/index/index', array('store_id'=>$distributor['distributor_id']));
    	$store_url = str_replace('index.php', 'wap/index.php', $store_url);
    	$this->assign('store_url', $store_url);
    	
    	//导航菜单
    	$params = array(
    			'is_show'=>1,
    			'distributor_id'=>$store_id
    	);
    	$nav = $this->navService()->getPagerList($params);
    	$this->assign('store_nav_list', $nav['list']);
    	
    	//商品标签
    	$params = array(
    			'distributor_id'=>$store_id,
    			'nav_show'=>1,
    			'pagesize'=>6,
    	);
    	$datas = $this->goodsLabelService()->getPagerList($params);
    	$this->assign('goodslabel_list', $datas['list']);
		
		
    	//案例列表
		$store_id=I('store_id')?I('store_id'):I('get.store_id');
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$decorate_style=I('decorate_style')?I('decorate_style'):I('get.decorate_style');
		$house_type=I('house_type')?I('house_type'):I('get.house_type');
		$hot_params=$map=array('distributor_id'=>$store_id);
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map,'layout_type'=>$house_type,'decorate_type'=>$decorate_style);
		$result=$this->designerCaseService()->infoPagerList($params);
    	$pager=new Pager($result['count'],$size);
		$pager->setConfig('header','');
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		
		if(IS_AJAX){
			$html=$this->renderPartial('_index');
			$this->ajaxReturn(array('html'=>$html,'page'=>$pager->show()));
		}
		
		//热门案例
		$hot_params['pagesize']=6;
		$hot_params['orderby']='click_number desc';
		$hot_result=$this->designerCaseService()->infoPagerList($hot_params);
		$this->assign('hot_list',$hot_result['list']);
		
		//筛选列表
		$type_list=$this->designerCaseService()->typeConfigPagerList(array(),'key,type_name,type_id');
		$type_key_list=$this->designerCaseService()->configGetField(array(),'id,name,type');
		foreach($type_list as $key=>$val){
			foreach($type_key_list as $key2=>$val2){
				if($key==$val2['type']){	
					$new_type_list[$val['type_id']]['type_name']=$val['type_name'];
					$new_type_list[$val['type_id']]['key']=$val['key'];
					$new_type_list[$val['type_id']]['list'][]=$val2;
				}
			}
		}
		$this->assign('type_list',$new_type_list);
		
		$this->display();
    }
    
    public function infoAction(){
		$case_id=I('get.case_id');
		$store_id=I('get.store_id');
		$info=$this->designerCaseService()->getInfo($case_id);
		if(empty($info)){
			$this->redirect('store/case/index',array('store_id'=>$store_id));
		}
		$info['gallery_array']=$info['gallery']!=''?unserialize($info['gallery']):array();
		$this->assign('info',$info);
		
		//浏览数+1
		caseAddClick($case_id);
		
		//热门案例
		$hot_params=array('map'=>array('distributor_id'=>$store_id));
		$hot_params['pagesize']=6;
		$hot_params['orderby']='click_number desc';
		$hot_result=$this->designerCaseService()->infoPagerList($hot_params);
		$this->assign('hot_list',$hot_result['list']);
		
		//获取该设计师案例列表
		$p=I('p')?I('p'):I('get.p');
		$size=6;
		$map=array('case_id'=>array('neq',$case_id),'distributor_id'=>$store_id);
		$params=array('page'=>$p,'pagesize'=>$size,'designer_id'=>$info['design_id'],'map'=>$map);
		$case_list=$this->designerCaseService()->infoPagerList($params);
		$pager=new Pager($case_list['count'],$size);
		$pager->setConfig('header','');
		foreach($case_list['list'] as $key=>$val){
			$case_list['list'][$key]['gallery_array']=$val['gallery']!=''?unserialize($val['gallery']):array();
		}
		$this->assign('case_list',$case_list['list']);
		$this->assign('page',$pager->show());
		
    	$this->display();
    }
	
	public function customAction(){
		if(IS_AJAX){
			$user_id=session('userid');
			if(empty($user_id)){
				$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
			}
			$store_id=I('store_id');
			$post=I('post.');
			unset($post['store_id']);
			$post['user_id']=$user_id;
			$post['distributor_id']=$store_id;
			
			try{
				$this->designerCustomService()->infoCreateOrModify($post);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功'));
		}
	}
	
	private function designerCustomService(){
		return D('DesignerCustom','Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase','Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsCatService(){
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function goodsLabelService(){
		return D('GoodsLabel', 'Service');
	}
}