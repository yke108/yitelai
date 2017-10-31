<?php
namespace Main\Controller\Design;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class CompanyController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '家装公司');
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
		
		$city_code=I('city_code')?I('city_code'):I('get.city_code');
		$city_code!='' && $params['city']=$city_code;
		
		//公司列表
		$params = array(
				'page'=>$p,
				'pagesize'=>6,
				//'map'=>array('admin_id'=>0),
				'distributor_type'=>1,
				'is_show'=>1,
				'status'=>Status::DistributorStatusNormal,
		);
		$datas = $this->distributorService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('page',$pager->show());
		
		if(IS_AJAX){
			$html=$this->renderPartial('_index');
			$this->ajaxReturn(array('html'=>$html,'page'=>$pager->show()));
			die();
		}
		
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
		$this->assign('type_key_list',$type_key_list);
		
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
		
		//获取筛选条件-城市
		$city_list=$this->designerService()->getDesignerCity();
		$this->assign('city_list',$city_list);
		
		//精品推荐
		$params = array(
				'pagesize'=>4,
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
	
	public function infoAction($id = 0){
		//分销商
		$distributor = $this->distributorService()->getInfo($id);
		if (empty($distributor)) {
			$this->error('家装公司不存在');
		}
		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
		$this->assign('distributor', $distributor);
		
		//案例统计
		$params = array(
				'distributor_id'=>$distributor['distributor_id'],
		);
		$case_count = $this->designerCaseService()->caseCount($params);
		$this->assign('case_count', $case_count);
		
		//关注数量
		$params = array(
				'collect_type'=>Genre::CollectTypeCompany,
				'id_value'=>$distributor['distributor_id'],
		);
		$collect_count = $this->collectService()->collectCount($params);
		$this->assign('collect_count', $collect_count);
		
		//设计团队
		$designer_map=array('distributor_id'=>$distributor['distributor_id']);
		$designer_params=array('pagesize'=>6,'map'=>$designer_map);
		$designer_result=$this->designerService()->infoPagerList($designer_params);
		$this->assign('designer_count',$designer_result['count']);
		$this->assign('designer_list',$designer_result['list']);
		
		//设计师等级
		$type_result=$this->designerTypeService()->typeValuePagerList(array('pagesize'=>1000));
		if($type_result['count']>0){
			$config_type=$this->designerTypeService()->getTypeField(array(),'key,type_name');
			foreach($type_result['list'] as $key=>$val){
				$type_list[$val['type']]['list'][$val['id']]=$val;
			}
			$this->assign('level_list',$type_list['level']['list']);
		}
		
		//家装设计案例
		$params=array('page'=>1,'pagesize'=>4,'distributor_id'=>$distributor['distributor_id']);
		$case_list=$this->designerCaseService()->infoPagerList($params);
		foreach($case_list['list'] as $key=>$val){
			$case_list['list'][$key]['gallery_array']=$val['gallery']!=''?unserialize($val['gallery']):array();
		}
		$this->assign('case_list',$case_list['list']);
		
		//热门案例
		$params=array('page'=>1,'pagesize'=>4,'distributor_id'=>$distributor['distributor_id'],'is_hot'=>1);
		$case_list=$this->designerCaseService()->infoPagerList($params);
		foreach($case_list['list'] as $key=>$val){
			$case_list['list'][$key]['gallery_array']=$val['gallery']!=''?unserialize($val['gallery']):array();
		}
		$this->assign('case_hot_list',$case_list['list']);
		
		$params = array(
				'page'=>1,
				'pagesize'=>7,
				'distributor_id'=>$distributor['distributor_id'],
		);
		$result=$this->designerService()->orderPagerList($params);
		$this->assign('order_list', $result['list']);
		
		//筛选列表
		$type_list=$this->designerCaseService()->typeConfigPagerList(array(),'key,type_name,type_id');
		$type_key_list=$this->designerCaseService()->configGetField(array(),'id,name,type');
		foreach($type_list as $key=>$val){
			foreach($type_key_list as $key2=>$val2){
				if($key==$val2['type']){
					$new_type_list[$val['type_id']]['type_name']=$val['type_name'];
					$new_type_list[$val['type_id']]['key']=$val['key'];
					$new_type_list[$val['type_id']]['list'][$val2['id']]=$val2;
				}
			}
		}
		$this->assign('type_list',$new_type_list);
		$this->assign('type_key_list',$type_key_list);
		
		//推荐品类
		$map = array(
				'a.product_num'=>array('gt', 0)
		);
		$params = array(
				'pagesize'=>4,
				'map'=>$map,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('recommend_list', $datas['list']);
		
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
		//
	
		$this->display();
	}
	
	public function orderAction(){
		if(IS_AJAX){
			if(session('userid')==''){
				$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
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
				
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功','url'=>U('info', array('id'=>$params['store_id']))));
		}
	
	}
	
	//提交关注
	public function collectAction($id = 0){
		$userid = session('userid');
		if (empty($userid)) {
			$this->error('请先登录', U('index/site/login'));
		}
		
		$collect_params = array(
				'user_id'=>$this->user['user_id'],
				'id_value'=>$id,
				'collect_type'=>Genre::CollectTypeCompany,
		);
		$collect = $this->collectService()->findInfo($collect_params);
	
		if ($collect) {
			$map = array(
					'id_value'=>$id,
					'user_id'=>session('userid'),
					'collect_type'=>Genre::CollectTypeCompany
			);
			try {
				$res = $this->collectService()->delCollect($map);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$params = array(
					'collect_type'=>Genre::CollectTypeCompany,
					'id_value'=>$id
			);
			$collect_count = $this->collectService()->collectCount($params);
			
			$this->success('取消关注成功', '', array('collect_count'=>$collect_count));
		}else {
			$data = array(
					'id_value'=>$id,
					'user_id'=>session('userid'),
					'collect_type'=>Genre::CollectTypeCompany
			);
			try {
				$res = $this->collectService()->addStore($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$params = array(
					'collect_type'=>Genre::CollectTypeCompany,
					'id_value'=>$id
			);
			$collect_count = $this->collectService()->collectCount($params);
			
			$this->success('关注成功', '', array('collect_count'=>$collect_count));
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