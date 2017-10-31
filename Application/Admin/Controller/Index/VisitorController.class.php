<?php
namespace Admin\Controller\Index;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class VisitorController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();

		$set = array(
			'in'=>'index',
			'ac'=>'index_visitor_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
		$content = I('content');//
		$front_start_time = I('front_start_time');//前日期 开始时间
		$front_end_time = I('front_end_time');//前日期 结束时间
		$to_start_time = I('to_start_time');//后日期 开始时间
		$to_end_time = I('to_end_time');//后日期 结束时间
		$region_code = I('region_code');//
		$shop_id = I('shop_id');//
		$province = I('province');//
		$city = I('city');//
		$province_city_area = I('province_city_area');//
		$this->assign('content', $content);
		$this->assign('front_start_time', $front_start_time);
		$this->assign('front_end_time', $front_end_time);
		$this->assign('to_start_time', $to_start_time);
		$this->assign('to_end_time', $to_end_time);
		$this->assign('region_code', $region_code);
		$this->assign('shop_id', $shop_id);
		$this->assign('provinces', $province);
		$this->assign('citys', $city);
		$this->assign('province_city_area', $province_city_area);

		//PC 访问量
		$webAskForm = $this->WebAskService()->webAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$webAskFormCount = $webAskForm['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$webAskTo = $this->WebAskService()->webAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$webAskToCount = $webAskTo['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$diffWebValueName = diffValue($webAskTo['count'] - $webAskForm['count']);
		//PC 访问量

		//微信 访问量
		$wechatAskForm = $this->WechatAskService()->WechatAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$wechatAskFormCount = $wechatAskForm['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$wechatAskTo = $this->WechatAskService()->WechatAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$wechatAskToCount = $wechatAskTo['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$diffWechatValueName = diffValue($wechatAskTo['count'] - $wechatAskForm['count']);
		//微信 访问量

		//会员 访问量
		$userAskForm = $this->UserAskService()->userAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$userAskFormCount = $userAskForm['count'];
		$this->assign('userAskFormCount', $userAskForm['count']);
		//-------------------------------------------------------------------------------------------------------------------------------//
		$userAskTo = $this->UserAskService()->userAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$userAskToCount = $userAskTo['count'];
		$this->assign('userAskToCount', $userAskTo['count']);
		//-------------------------------------------------------------------------------------------------------------------------------//
		$diffUserValueName = diffValue($userAskTo['count'] - $userAskForm['count']);
		//会员 访问量

		//游客 访问量
		$touristAskForm = $this->TouristAskService()->touristAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$touristAskFormCount = $touristAskForm['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$touristAskTo = $this->TouristAskService()->touristAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$touristAskToCount = $touristAskTo['count'];
		//-------------------------------------------------------------------------------------------------------------------------------//
		$diffTouristValue = diffValue($touristAskTo['count'] - $touristAskForm['count']);
		//游客 访问量

		//start 总访问量
		$totalVisitsFront =$webAskForm['count'] + $userAskForm['count'] + $touristAskForm['count'] + $wechatAskForm['count'];
		$totalVisitsTo = $webAskTo['count'] + $userAskTo['count'] + $touristAskTo['count'] + $wechatAskTo['count'];
		$diffTotalVisitsValue = diffValue($totalVisitsTo - $totalVisitsFront);
		//end 总访问量

		$this->assign('showFrontName', showFrontTimeName($front_start_time, $front_end_time));
		$this->assign('showToName', showToTimeName($to_start_time, $to_end_time));
		$_list_front_to_value = array(
			array('title' => "总访问量", 'front_value' => $totalVisitsFront, 'to_value' => $totalVisitsTo, 'diff_value' => $diffTotalVisitsValue,),
			array('title' => "PC访问量", 'front_value' => $webAskFormCount, 'to_value' => $webAskToCount, 'diff_value' => $diffWebValueName,),
			array('title' => "公众号访问量", 'front_value' => $wechatAskFormCount, 'to_value' => $wechatAskToCount, 'diff_value' => $diffWechatValueName,),
			array('title' => "会员访问量", 'front_value' => $userAskFormCount, 'to_value' => $userAskToCount, 'diff_value' => $diffUserValueName,),
			array('title' => "游客访问量", 'front_value' => $touristAskFormCount, 'to_value' => $touristAskToCount, 'diff_value' => $diffTouristValue,),
		);
		$this->assign('list_front_to_value', $_list_front_to_value);

		//start 商家
		$distributorInfoList = $this->distributorInfoService()->distributorInfoList(array(), array('distributor_id', 'distributor_name'));
		$this->assign('distributorInfoList', $distributorInfoList);
		//end 商家
		$this->assign('page_title', '访客分析');

		$params = array(
			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
			'pagesize' => 10,
		);
		$result = $this->visitorAskService()->pageVisitorAskList($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $this->pagesize);
		$this->assign('pager', $pager->show());

		$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	$this->display();
    }

	public function visitsdetailAction()/*访客分析-详情*/
	{
		$visitor_ask_id = I('get.visitor_ask_id');
		$visitorAskFind = $this->visitorAskService()->visitorAskDetail($visitor_ask_id);
		$this->assign('visitorAskFind',$visitorAskFind);

		//访问文章
		$inputtime = date('Y-m-d',$visitorAskFind['inputtime']);
		$articleArray = array();
		$articleArray['user_id'] = $visitorAskFind['user_id'];
		$articleArray['starTime'] = $inputtime;
		$articleArray['endTime'] = $inputtime;
		$articleArray['collect_type'] = 8;
		$articleList = $this->collectService()->collectVisitorList($articleArray);
		$this->assign('articleList',$articleList['list']);

		//文章产品
		$goodsArray = array();
		$goodsArray['user_id'] = $visitorAskFind['user_id'];
		$goodsArray['starTime'] = $inputtime;
		$goodsArray['endTime'] = $inputtime;
		$goodsArray['collect_type'] = 2;
		$goodsList = $this->collectService()->collectVisitorList($goodsArray);
		$this->assign('goodsList',$goodsList['list']);

		//购买车产品
		$cartArray = array();
		$cartArray['user_id'] = $visitorAskFind['user_id'];
		$cartArray['starTime'] = $inputtime;
		$cartArray['endTime'] = $inputtime;
		$cartList = $this->cartInfoService()->cartInfoList($cartArray);
		$this->assign('cartList',$cartList);

		$this->assign('page_title', '访客分析-详情');
		$this->display();
	}

	private function cartInfoService(){
		return D('Cart', 'Service');
	}

	private function collectService(){
		return D('Collect', 'Service');
	}

	private function regionService(){
		return D('Region', 'Service');
	}

	public function visitorAskService()
	{
		return D('VisitorAsk', 'Service');
	}

	public function payBuyersAskService()
	{
		return D('PayBuyersAsk', 'Service');
	}

	public function afterSalesService()
	{
		return D('AfterSales', 'Service');
	}

	private function WechatAskService()
	{
		return D('WechatAsk', 'Service');
	}

	private function TouristAskService()
	{
		return D('TouristAsk', 'Service');
	}

	private function UserAskService()
	{
		return D('UserAsk', 'Service');
	}

	private function WebAskService()
	{
		return D('WebAsk', 'Service');
	}

	private function totalAskService()
	{
		return D('TotalAsk', 'Service');
	}

	private function distributorInfoService()
	{
		return D('DistributorInfo', 'Service');
	}
    
}