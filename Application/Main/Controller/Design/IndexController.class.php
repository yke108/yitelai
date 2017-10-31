<?php
namespace Main\Controller\Design;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Genre;

class IndexController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '设计师');
    }
	
    public function indexAction(){
		
		//获取设计师列表
    	$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=8;
		$recommend_params=$params=array('page'=>$p,'pagesize'=>$size,'get_case'=>1);
		
		//筛选条件
		$demand=I('demand')?I('demand'):I('get.demand');//需求空间
		$demand!='' && $params['demand']=$demand;
		
		$charge=I('charge')?I('charge'):I('get.charge');//设计师收费
		$charge!='' && $params['charge']=$charge;
		
		$decorate=I('decorate')?I('decorate'):I('get.decorate');//风格
		$decorate!='' && $params['decorate']=$decorate;
		
		$city_code=I('city_code')?I('city_code'):I('get.city_code');
		$city_code!='' && $params['city']=$city_code;
		
		$result=$this->designerService()->infoPagerList($params);
		$pager=new Pager($result['count'],$size);
		$pager->setConfig('header','');
		$pager->setConfig('first','首页');
		$pager->setConfig('last','尾页');
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		
		if(IS_AJAX){
			$html=$this->renderPartial('_index');
			$this->ajaxReturn(array('html'=>$html,'page'=>$pager->show()));
			die();
		}
		
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
		
		
		//推荐页面首页列表
		$recommend_params['is_page_top']=1;
		$recommend_result=$this->designerService()->infoPagerList($recommend_params);
		$this->assign('recommend_list',$recommend_result['list']);
		
		
		$type_map=array('type'=>array('in','charge,space'));
		$type_result=$this->designerTypeService()->typeValuePagerList(array('map'=>$type_map,'pagesize'=>1000));
		if($type_result['count']>0){
			$config_type=$this->designerTypeService()->getTypeField(array('key'=>array('in','charge,space')),'key,type_name');
			foreach($type_result['list'] as $key=>$val){
				$type_list[$val['type']]['list'][]=$val;
				$type_list[$val['type']]['name']=$config_type[$val['type']];
				$type_list[$val['type']]['key']=$val['type'];
			}
			$this->assign('type_list',$type_list);
		}
		//var_dump($type_list);die();
		
		//风格
		$list = $this->designerCaseService()->configAllList();
		if ($list){
			foreach($list as $key=>$val){
				$new_list[$val['type']]['list'][]=$val;
				$new_list[$val['type']]['name']=$config_type[$val['type']];
			}
			$this->assign('case_list', $new_list);
		}
		
		//获取筛选条件-城市
		$city_list=$this->designerService()->getDesignerCity();
		$this->assign('city_list',$city_list);
		
		//精品推荐
		$params = array(
				'pagesize'=>10,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('best_list', $datas['list']);
		
		//定制类型
		$params = array(
				'is_show'=>1,
		);
    	$result = $this->designerMessageTypeService()->infoPagerList($params);
    	$this->assign('message_type_list', $result['list']);
		
		$this->display();
    }
	
	public function infoAction(){
		
		$design_id=I('id')?I('id'):I('get.id');
		$info=$this->designerService()->getInfo($design_id);
		if(empty($design_id)){
			$this->redirect('index');
		}
		$this->assign('info',$info);
		
		//访问量
		try {
			$this->designerService()->viewnum($design_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		//案例
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$params=array('page'=>$p,'pagesize'=>$size,'designer_id'=>$design_id);
		$case_list=$this->designerCaseService()->infoPagerList($params);
		foreach($case_list['list'] as $key=>$val){
			$case_list['list'][$key]['gallery_array']=$val['gallery']!=''?unserialize($val['gallery']):array();
		}
		$this->assign('case_list',$case_list['list']);
		
		//预约列表
		$size=7;
		$params = array(
			'page'=>$p,
			'pagesize'=>$size,
			'designer_id'=>$design_id,
		);
		$result=$this->designerService()->orderPagerList($params);
		$this->assign('order_list', $result['list']);
		
		$this->display();
	}
	
	public function messageAction(){
		if(IS_AJAX){
			/* if(session('userid')==''){
				$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
			} */
			$params=I('post.');
			$params['user_id']=session('userid');
			$params['from_url'] = DK_DOMAIN;
			try{
				$result = $this->designerMessageService()->infoCreateOrModify($params);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功','message_count'=>$result['message_count']));
		}
		
	}
	public function orderAction(){
		if(IS_AJAX){
			if(session('userid')==''){
				$this->ajaxReturn(array('error'=>2,'msg'=>'请登录'));
			}
			$params=I('post.');
			$designer_id=$params['designer_id'];
			//$designer_info=$this->designerService()->getInfo($designer_id);
//			if(empty($designer_info)){
//				$this->ajaxReturn(array('error'=>1,'msg'=>'设计师不存在'));
//			}
			$status_info=$this->designerService()->findStatus(1);
			
			$params['user_id']=session('userid');
			$params['customer_name']=$params['nick_name'];
			$params['customer_mobile']=$params['mobile'];
			$params['status_id']=$status_info['status_id'];
			$region_code=$params['district'];
			$params['province']=intval($region_code/10000)*10000;
			$params['city']=intval($region_code/100)*100;
			$params['district']=$region_code;
			
			try{
				$this->designerService()->orderCreateOrModify($params);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功','url'=>U('info', array('id'=>$designer_id))));
		}
		
	}
	
	public function case_listAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		//预约列表
		$size=7;
		$params = array(
				'page'=>1,
				'pagesize'=>$size,
		);
		$result=$this->designerService()->orderPagerList($params);
		$this->assign('order_list', $result['list']);
		
		//案例列表
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>20
		);
		if ($get['keyword']) {
			$params['keyword'] = $get['keyword'];
		}
		$result = $this->designerCaseService()->infoPagerList($params);
		$this->assign('case_list', $result['list']);
		$pager=new Pager($result['count'], $params['pagesize']);
		$this->assign('page',$pager->show_pc());
		
		$this->display();
	}
	
	public function case_infoAction(){
		$id=I('id')?I('id'):I('get.id');
		$info=$this->designerCaseService()->getInfo($id);
		if(empty($info)){
			$this->redirect('Index/index/index');
		}
		$info['gallery_array']=$info['gallery']!=''?unserialize($info['gallery']):array();
		$this->assign('info',$info);
		
		$designer_info=$this->designerService()->getInfo($info['designer_id'],session('userid'));
		$this->assign('designer_info',$designer_info);
		
		//店铺
		$distributor_info = $this->distributorService()->getInfo($designer_info['distributor_id']);
		$this->assign('distributor_info',$distributor_info);
		
		//浏览数+1
		caseAddClick($id);
		
		//其他案例
		$case_map=array('case_id'=>array('neq',$id),'designer_id'=>$info['designer_id']);
		$case_params=array('page'=>1,'pagesize'=>3,'map'=>$case_map,'orderby'=>"rand()");
		$other_list=$this->designerCaseService()->infoPagerList($case_params);
		$this->assign('other_list',$other_list['list']);
		
		//添加案例浏览记录
		$log_params=array('user_id'=>session('userid'),'case_id'=>$id,'designer_id'=>$info['designer_id']);
		$this->designerCaseService()->caseLogCreate($log_params);
		
		//案例浏览列表
		$log_record=array('user_id'=>session('userid'),'page'=>1,'pagesize'=>4);
		$case_record_list=$this->designerCaseService()->caseLogPagerList($log_record);
		$this->assign('case_record_list',$case_record_list);
		
		//案例字段
		$case_config=$this->designerCaseService()->configGetField(array(),'id,name');
		$this->assign('case_config',$case_config);
		
		$this->display();
	}
	
	public function bespeakAction(){
		if(session('userid')==''){
			$this->redirect('index/site/login');
		}
		$designer_id=I('id')?I('id'):I('get.id');
		$store_id=I('store_id')?I('store_id'):I('get.store_id');
		$this->assign('store_id',$store_id);
		
		if($designer_id=='' && $store_id==''){
			$this->redirect('index/index/index');
		}
		
		//获取设计师
		$designer_info = $this->designerService()->getInfo($designer_id, session('userid'));
		$this->assign('designer_info',$designer_info);
		
		//获取筛选条件列表
		$filter=$this->configService()->findConfigs(array('in','design_case,design'));
		foreach($filter as $key=>$val){
			$filter[$key]=array_filter_value(explode("\r\n",$val));
		}
		$this->assign('filter',$filter);
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
		
		//获取摄影师订单数
		$order_count=$this->designerService()->orderCount($designer_id);
		$this->assign('order_count',$order_count);
		
		//获取擅长空间和风格
		$map['id'] = array('in', $designer_info['demand']);
		$params = array('map'=>$map, 'pagesize'=>1000);
		$type_result=$this->designerTypeService()->typeValuePagerList($params);
		if($type_result['count']>0){
			$config_type=$this->designerTypeService()->getTypeField(array(),'key,type_name');
			foreach($type_result['list'] as $key=>$val){
				$type_list[$val['type']]['list'][]=$val;
				$type_list[$val['type']]['name']=$config_type[$val['type']];
				$type_list[$val['type']]['key']=$val['type'];
			}
			$this->assign('type_list',$type_list);
		}
		
		
		$map['id'] = array('in', $designer_info['decorate']);
		$params = array('map'=>$map);
		$datas = $this->designerCaseService()->configPagerList($params);
		$this->assign('decorate_list', $datas['list']);
		
		$this->display();
	}
	
	//提交关注
	public function add_followAction(){
		if(IS_AJAX){
			if(session('userid')==''){
				$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
			}
			$designer_id=I('designer_id')?I('designer_id'):I('get.designer_id');
			$params=array('designer_id'=>$designer_id,'user_id'=>session('userid'));
			
			try{
				$this->designerService()->addFollow($params);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'关注成功'));
		}
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage', 'Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase', 'Service');
	}
	
	
	
	private function regionService(){
		return D('Region','Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function designerTypeService(){
		return D('DesignerType','Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function collectService(){
		return D('Collect', 'Service');
	}
	
	private function designerMessageTypeService(){
		return D('DesignerMessageType', 'Service');
	}
}