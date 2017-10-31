<?php
namespace Home\Controller\Analysis;

use Home\Controller\BaseController;
use Common\Basic\Genre;

class PlatformController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }

    protected function _purviewCheck(){
        $this->purviewCheck('index');
    }

    public function indexAction()/*平台分析*/
    {
        $front_start_time = I('front_start_time');//前日期 开始时间
        $front_end_time = I('front_end_time');//前日期 结束时间
        $to_start_time = I('to_start_time');//后日期 开始时间
        $to_end_time = I('to_end_time');//后日期 结束时间
        $region_code = I('region_code');//
        $shop_id = I('shop_id');//
        $province = I('province');//
        $city = I('city');//
        $province_city_area = I('province_city_area');//
        $this->assign('front_start_time', $front_start_time);
        $this->assign('front_end_time', $front_end_time);
        $this->assign('to_start_time', $to_start_time);
        $this->assign('to_end_time', $to_end_time);
        $this->assign('region_code', $region_code);
        $this->assign('shop_id', $shop_id);
        $this->assign('province', $province);
        $this->assign('city', $city);
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

        //start 店铺统计

        //start 店铺统计
        //新的店铺
        $newFrontShopTotal = $this->distributorInfoService()->totalFrontDistributorInfo($front_start_time, $front_end_time, $region_code, 2);
        $newToShopTotal = $this->distributorInfoService()->totalToDistributorInfo($front_start_time, $front_end_time, $region_code, 2);
        $diffAfterNewShopValue = diffValue($newToShopTotal - $newFrontShopTotal);

        //闭关店铺
        $closeFrontShopTotal = $this->distributorInfoService()->totalFrontDistributorInfo($front_start_time, $front_end_time, $region_code, 3);
        $closeToShopTotal = $this->distributorInfoService()->totalToDistributorInfo($front_start_time, $front_end_time, $region_code, 3);
        $diffAfterCloseShopValue = diffValue($closeToShopTotal - $closeFrontShopTotal);

        //总店铺
        $totalFrontShopTotal = $this->distributorInfoService()->totalFrontDistributorInfo($front_start_time, $front_end_time, $region_code, '');
        $totalToShopTotal = $this->distributorInfoService()->totalToDistributorInfo($front_start_time, $front_end_time, $region_code, '');
        $diffAfterTotalShopValue = diffValue($totalToShopTotal - $totalFrontShopTotal);
        //end 店铺统计

        //start 支付买家数
        $payBuyersAskForm = $this->payBuyersAskService()->payBuyersAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
        $payBuyersAskFormCount = $payBuyersAskForm['count'];
        $payBuyersAskTo = $this->payBuyersAskService()->payBuyersAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
        $payBuyersAskToCount = $payBuyersAskTo['count'];
        $diffPayBuyersValue = diffValue($payBuyersAskToCount - $payBuyersAskFormCount);
        //end 支付买家数


        //start 访客数
        $visitorAskFrontCount = $this->visitorAskService()->visitorAskFrontCount($front_start_time, $front_end_time, $region_code, $shop_id);
        $visitorAskFrontCount = $visitorAskFrontCount['count'];
        $visitorAskToCount = $this->visitorAskService()->visitorAskToCount($to_start_time, $to_end_time, $region_code, $shop_id);
        $visitorAskToCount = $visitorAskToCount['count'];
        $diffVisitorValue = diffValue($visitorAskToCount - $visitorAskFrontCount);
        //end 访客数


        //start 总访问量
        $totalVisitsFront =$totalAskForm['count'] + $webAskForm['count'] + $userAskForm['count'] + $touristAskForm['count'] + $wechatAskForm['count'] + $visitorAskFrontCount['count'];
        $totalVisitsTo =$totalAskTo['count'] + $webAskTo['count'] + $userAskTo['count'] + $touristAskTo['count'] + $wechatAskTo['count'] + $visitorAskToCount['count'];
        $diffTotalVisitsValue = diffValue($totalVisitsTo - $totalVisitsFront);
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
            array('title' => "转化率", 'front_value' => 'null%', 'to_value' => 'null%', 'diff_value' => 'null%',),
            array('title' => "新店数", 'front_value' => $newFrontShopTotal, 'to_value' => $newToShopTotal, 'diff_value' => $diffAfterNewShopValue,),
            array('title' => "关闭店数", 'front_value' => $closeFrontShopTotal, 'to_value' => $closeToShopTotal, 'diff_value' => $diffAfterCloseShopValue,),
            array('title' => "总店数", 'front_value' => $totalFrontShopTotal, 'to_value' => $totalToShopTotal, 'diff_value' => $diffAfterTotalShopValue,),
            array('title' => "退单数量", 'front_value' => $afterSalesFrontCount, 'to_value' => $afterSalesToCount, 'diff_value' => $diffAfterSalesCountValue,),
            array('title' => "退单金额", 'front_value' => $afterSalesFrontAmount, 'to_value' => $afterSalesToAmount, 'diff_value' => $diffAfterSalesCountAmount,),
        );
        $this->assign('list_front_to_value', $_list_front_to_value);

        //start 商家
        $distributorInfoList = $this->distributorInfoService()->distributorInfoList(array(), array('distributor_id', 'distributor_name'));
        $this->assign('distributorInfoList', $distributorInfoList);
        //end 商家
        $this->assign('page_title', '平台分析');
        $this->display();
    }

    public function visitsanalysisAction()/*访客分析*/
    {
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
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('province_city_area', $province_city_area);

        //PC 访问量
        $webAskForm = $this->WebAskService()->webAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
        $webAskFormCount = $webAskForm['count'];
        $webAskTo = $this->WebAskService()->webAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
        $webAskToCount = $webAskTo['count'];
        $diffWebValueName = diffValue($webAskTo['count'] - $webAskForm['count']);
        //PC 访问量


        //微信 访问量
        $wechatAskForm = $this->WechatAskService()->WechatAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
        $wechatAskFormCount = $wechatAskForm['count'];
        $wechatAskTo = $this->WechatAskService()->WechatAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
        $wechatAskToCount = $wechatAskTo['count'];
        $diffWechatValueName = diffValue($wechatAskTo['count'] - $wechatAskForm['count']);
        //微信 访问量

        //会员 访问量
        $userAskForm = $this->UserAskService()->userAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
        $userAskFormCount = $userAskForm['count'];
        $this->assign('userAskFormCount', $userAskForm['count']);
        $userAskTo = $this->UserAskService()->userAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
        $userAskToCount = $userAskTo['count'];
        $this->assign('userAskToCount', $userAskTo['count']);
        $diffUserValueName = diffValue($userAskTo['count'] - $userAskForm['count']);
        //会员 访问量

        //游客 访问量
        $touristAskForm = $this->TouristAskService()->touristAskFrontList($front_start_time, $front_end_time, $region_code, $shop_id);
        $touristAskFormCount = $touristAskForm['count'];
        $touristAskTo = $this->TouristAskService()->touristAskToList($to_start_time, $to_end_time, $region_code, $shop_id);
        $touristAskToCount = $touristAskTo['count'];
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
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $result = $this->visitorAskService()->pageVisitorAskList($params);
        $this->assign('list', $result['list']);
        $this->assign('count', $result['count']);
        if (IS_AJAX) {
            if (empty($result['list'])) {
                $clist = '';
            } else {
                $clist = $this->renderPartial('_visits_list');
            }
            $this->ajaxReturn(array('html' => $clist, 'p' => $params['page'] + 1));
        }
        $this->assign('page_title', '访客分析');
        $this->display();
    }

    public function visitsdetailAction()/*访客分析-详情*/
    {
        $visitor_ask_id = I('get.visitor_ask_id');
        if(empty($visitor_ask_id)) $this->error("发生致命错误");
        $visitorAskFind = $this->visitorAskService()->visitorAskDetail($visitor_ask_id);
        if(empty($visitorAskFind)) $this->error("发生致命错误");
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

    private function orderService()
    {
        return D('Order', 'Service');
    }

    private function regionService()
    {
        return D('Region', 'Service');
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