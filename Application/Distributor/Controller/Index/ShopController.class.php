<?php
namespace Distributor\Controller\Index;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class ShopController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();

		$set = array(
			'in'=>'static',
			'ac'=>'index_shop_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
		$front_start_time = I('front_start_time');//前日期 开始时间
		$front_end_time = I('front_end_time');//前日期 结束时间
		$to_start_time = I('to_start_time');//后日期 开始时间
		$to_end_time = I('to_end_time');//后日期 结束时间
		$region_code = I('region_code');//
		$shop_id = $this->org_id;//
		$province = I('province');//
		$city = I('city');//
		$province_city_area = I('province_city_area');//
		$this->assign('front_start_time', $front_start_time);
		$this->assign('front_end_time', $front_end_time);
		$this->assign('to_start_time', $to_start_time);
		$this->assign('to_end_time', $to_end_time);
		$this->assign('region_code', $region_code);
		$this->assign('shop_id', $shop_id);
		$this->assign('provinces', $province);
		$this->assign('citys', $city);
		$this->assign('province_city_area', $province_city_area);

		//start 浏览量
		$totalAskForm = $this->totalAskService()->totalAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$totalAskFormCount = $totalAskForm['count'];
		$totalAskTo = $this->totalAskService()->totalAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$totalAskToCount = $totalAskTo['count'];
		$diffTotalValueName = diffValue($totalAskToCount - $totalAskFormCount);
		//end 浏览量

		//PC 访问量
		$webAskForm = $this->WebAskService()->webAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$webAskFormCount = $webAskForm['count'];
		$webAskTo = $this->WebAskService()->webAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$webAskToCount = $webAskTo['count'];
		$diffWebValueName = diffValue($webAskToCount - $webAskFormCount);
		//PC 访问量


		//微信 访问量
		$wechatAskForm = $this->WechatAskService()->WechatAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$wechatAskFormCount = $wechatAskForm['count'];
		$wechatAskTo = $this->WechatAskService()->WechatAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$wechatAskToCount = $wechatAskTo['count'];
		$diffWechatValueName = diffValue($wechatAskToCount - $wechatAskFormCount);
		//微信 访问量

		//会员 访问量
		$userAskForm = $this->UserAskService()->userAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$userAskFormCount = $userAskForm['count'];
		$userAskTo = $this->UserAskService()->userAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$userAskToCount = $userAskTo['count'];
		$diffUserValueName = diffValue($userAskToCount - $userAskFormCount);
		//会员 访问量

		//游客 访问量
		$touristAskForm = $this->TouristAskService()->touristAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$touristAskFormCount = $touristAskForm['count'];
		$touristAskTo = $this->TouristAskService()->touristAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$touristAskToCount = $touristAskTo['count'];
		$diffTouristValue = diffValue($touristAskToCount - $touristAskFormCount);
		//游客 访问量

		//订单 支付数量和支付金额
		$orderFront = $this->orderService()->orderTotalFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$orderFrontCount = $orderFront['count'];
		$orderFrontAmount = $orderFront['order_amount'];
		//round($number ,2)
		$orderTo = $this->orderService()->orderTotalTotList($to_start_time, $to_end_time, $region_code, $shop_id);
		$orderToCount = $orderTo['count'];
		$orderToAmount = $orderTo['order_amount'];
		$diffOrderCountValue = diffValue($orderToCount - $orderFrontCount);
		$diffOrderCountAmount = diffValue($orderToAmount - $orderFrontAmount);
		//订单 支付数量和支付金额


		//start 客单价
		$orderUserFrontCount = $this->orderService()->orderUserFrontCount($front_start_time, $front_end_time, $region_code, $shop_id);
		$orderUserFrontAmount = round(($orderFront['order_amount'] / $orderUserFrontCount['count']), 2);
		$orderUserToCount = $this->orderService()->orderUserToCount($to_start_time, $to_end_time, $region_code, $shop_id);
		$orderUserToAAmount = round(($orderTo['order_amount'] / $orderUserToCount['count']), 2);
		$diffOrderUserCountValue = diffValue($orderUserToAAmount - $orderUserFrontAmount);
		//end 客单价

		//start  退款数量和退款金额
		$afterSalesFront = $this->afterSalesService()->afterSalesFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$afterSalesFrontCount = $afterSalesFront['count'];
		$afterSalesFrontAmount = $afterSalesFront['back_money'];
		$afterSalesTo = $this->afterSalesService()->afterSalesToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$afterSalesToCount = $afterSalesTo['count'];
		$afterSalesToAmount = $afterSalesTo['back_money'];

		$diffAfterSalesCountValue = diffValue($afterSalesToCount - $afterSalesFrontCount);
		$diffAfterSalesCountAmount = diffValue($afterSalesToAmount - $afterSalesFrontAmount);
		//end 退款数量和退款金额

		//start 支付买家数
		$payBuyersAskForm = $this->payBuyersAskService()->payBuyersAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
		$payBuyersAskFormCount = $payBuyersAskForm['count'];
		$payBuyersAskTo = $this->payBuyersAskService()->payBuyersAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
		$payBuyersAskToCount = $payBuyersAskTo['count'];
		$diffPayBuyersValue = ($payBuyersAskToCount - $payBuyersAskFormCount);
		//end 支付买家数


		//start 访客数
		$visitorAskFrontCount = $this->visitorAskService()->visitorAskFrontCount($front_start_time, $front_end_time, $region_code, $shop_id);
		$visitorAskFrontCount = $visitorAskFrontCount['count'];
		$this->assign('visitorAskFrontCount', $visitorAskFrontCount['count']);
		$visitorAskToCount = $this->visitorAskService()->visitorAskToCount($to_start_time, $to_end_time, $region_code, $shop_id);
		$visitorAskToCount = $visitorAskToCount['count'];
		$diffVisitorValue = diffValue($visitorAskToCount['count'] - $visitorAskFrontCount['count']);
		//end 访客数


		//start 总访问量
		$totalVisitsFront =$totalAskForm['count'] + $webAskForm['count'] + $userAskForm['count'] + $touristAskForm['count'] + $wechatAskForm['count'] + $visitorAskFrontCount['count'];
		$this->assign('totalVisitsFront', $totalVisitsFront);
		$totalVisitsTo =$totalAskTo['count'] + $webAskTo['count'] + $userAskTo['count'] + $touristAskTo['count'] + $wechatAskTo['count'] + $visitorAskToCount['count'];
		$this->assign('totalVisitsTo', $totalVisitsTo);
		$diffTotalVisitsValue = $totalVisitsTo - $totalVisitsFront;
		if ($diffTotalVisitsValue > 0) {
			$this->assign('diffTotalVisitsValue', "<em>+" . $diffTotalVisitsValue . "</em>");
		} else {
			$this->assign('diffTotalVisitsValue', "<span>" . $diffTotalVisitsValue . "</span>");
		}
		//end 总访问量

		$this->assign('showFrontName', showFrontTimeName($front_start_time, $front_end_time));
		$this->assign('showToName', showToTimeName($to_start_time, $to_end_time));
		$_list_front_to_value = array(
			array('title' => "总访问量", 'front_value' => $totalVisitsFront, 'to_value' => $totalVisitsTo, 'diff_value' => $diffTotalVisitsValue,),
			array('title' => "PC访问量", 'front_value' => $webAskFormCount, 'to_value' => $webAskToCount, 'diff_value' => $diffWebValueName,),
			array('title' => "微信公众号", 'front_value' => $wechatAskFormCount, 'to_value' => $wechatAskToCount, 'diff_value' => $diffWechatValueName,),
			array('title' => "会员访问量", 'front_value' => $userAskFormCount, 'to_value' => $userAskToCount, 'diff_value' => $diffUserValueName,),
			array('title' => "游客访问量", 'front_value' => $touristAskFormCount, 'to_value' => $touristAskToCount, 'diff_value' => $diffTouristValue,),
			array('title' => "访客数", 'front_value' => $visitorAskFrontCount, 'to_value' => $visitorAskToCount, 'diff_value' => $diffVisitorValue,),
			array('title' => "浏览量", 'front_value' => $totalAskFormCount, 'to_value' => $totalAskToCount, 'diff_value' => $diffTotalValueName,),
			array('title' => "支付金额", 'front_value' => $orderFrontAmount, 'to_value' => $orderToAmount, 'diff_value' => $diffOrderCountAmount,),
			array('title' => "支付订单数", 'front_value' => $orderFrontCount, 'to_value' => $orderToCount, 'diff_value' => $diffOrderCountValue,),
			array('title' => "支付买家数", 'front_value' => $payBuyersAskFormCount, 'to_value' => $payBuyersAskToCount, 'diff_value' => $diffPayBuyersValue,),
			array('title' => "客单价", 'front_value' => $orderUserFrontAmount, 'to_value' => $orderUserToAAmount, 'diff_value' => $diffOrderUserCountValue,),
			/*array('title' => "转化率", 'front_value' => '', 'to_value' => '', 'diff_value' => '',),*/
			array('title' => "退单数量", 'front_value' => $afterSalesFrontCount, 'to_value' => $afterSalesToCount, 'diff_value' => $diffAfterSalesCountValue,),
			array('title' => "退单金额", 'front_value' => $afterSalesFrontAmount, 'to_value' => $afterSalesToAmount, 'diff_value' => $diffAfterSalesCountAmount,),
		);
		$this->assign('list_front_to_value', $_list_front_to_value);
		$this->assign('page_title', '店铺分析');

		$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	$this->display();
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