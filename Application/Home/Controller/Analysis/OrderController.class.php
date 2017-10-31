<?php
namespace Home\Controller\Analysis;

use Home\Controller\BaseController;
use Common\Basic\Genre;

class OrderController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }

    public function indexAction()/*订单分析*/
    {
        $content = I('content');//
        $start_price = I('start_price');//
        $end_price = I('end_price');//
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
        $this->assign('start_price', $start_price);
        $this->assign('end_price', $end_price);
        $this->assign('front_start_time', $front_start_time);
        $this->assign('front_end_time', $front_end_time);
        $this->assign('to_start_time', $to_start_time);
        $this->assign('to_end_time', $to_end_time);
        $this->assign('region_code', $region_code);
        $this->assign('shop_id', $shop_id);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('province_city_area', $province_city_area);

        //start 订单总数
        $analysisOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id);
        $analysisOrderToTotal = $this->orderService()->analysisOrderToTotal('', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id);
        $diffOrderTotalValue = diffValue($analysisOrderToTotal - $analysisOrderFrontTotal);
        //end 订单总数

        //start 标准订单
        $standardOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('1,2', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id);
        $standardOrderToTotal = $this->orderService()->analysisOrderToTotal('1,2', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id);
        $diffStandardOrderTotalValue = diffValue($standardOrderToTotal - $standardOrderFrontTotal);
        //end 标准订单

        //start 待支付
        $stayOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('99', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id);
        $stayOrderToTotal = $this->orderService()->analysisOrderToTotal('99', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id);
        $diffStayOrderTotalValue = diffValue($stayOrderToTotal - $stayOrderFrontTotal);
        //end 待支付

        //start 待发货
        $deliverOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('99', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id);
        $deliverOrderToTotal = $this->orderService()->analysisOrderToTotal('99', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id);
        $diffDeliverOrderTotalValue = diffValue($deliverOrderToTotal - $deliverOrderFrontTotal);
        //end 待发货

        //start 已发货
        $shippedOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('99', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id);
        $shippedOrderToTotal = $this->orderService()->analysisOrderToTotal('99', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id);
        $diffShippedOrderTotalValue = $shippedOrderToTotal - $shippedOrderFrontTotal;
        $this->assign('shippedOrderFrontTotal',$shippedOrderFrontTotal);
        $this->assign('shippedOrderToTotal',$shippedOrderToTotal);
        if ($diffShippedOrderTotalValue > 0) {
            $this->assign('diffShippedOrderTotalValue', "<em>+" . $diffShippedOrderTotalValue . "</em>");
        } else {
            $this->assign('diffShippedOrderTotalValue', "<span>" . $diffShippedOrderTotalValue . "</span>");
        }
        //end 已发货

        //start 已取消
        $canceledOrderFrontTotal = $this->orderService()->analysisOrderFrontTotal('', '', $content, $start_price, $end_price, $front_start_time, $front_end_time, $region_code, $shop_id, '3');
        $canceledOrderToTotal = $this->orderService()->analysisOrderToTotal('', '', $content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id, '3');
        $diffCanceledOrderTotalValue = $canceledOrderToTotal - $canceledOrderFrontTotal;
        $this->assign('canceledOrderFrontTotal',$canceledOrderFrontTotal);
        $this->assign('canceledOrderToTotal',$canceledOrderToTotal);
        if ($diffCanceledOrderTotalValue > 0) {
            $this->assign('diffCanceledOrderTotalValue', "<em>+" . $diffCanceledOrderTotalValue . "</em>");
        } else {
            $this->assign('diffCanceledOrderTotalValue', "<span>" . $diffCanceledOrderTotalValue . "</span>");
        }
        //end 已取消

        $this->assign('showFrontName', showFrontTimeName($front_start_time, $front_end_time));
        $this->assign('showToName', showToTimeName($to_start_time, $to_end_time));

        $_list_front_to_value = array(
            array('title' => "订单总数", 'front_value' => $analysisOrderFrontTotal, 'to_value' => $analysisOrderToTotal, 'diff_value' => $diffOrderTotalValue, 'url' => 'javascript:void(0)'),
            array('title' => "标准订单", 'front_value' => $standardOrderFrontTotal, 'to_value' => $standardOrderToTotal, 'diff_value' => $diffStandardOrderTotalValue, 'url' => U('Analysis/Order/ordertypelist')),
            array('title' => "定制订单", 'front_value' => '', 'to_value' => '', 'diff_value' => '', 'url' => 'javascript:void(0)'),
            array('title' => "未付款", 'front_value' => $stayOrderFrontTotal, 'to_value' => $stayOrderToTotal, 'diff_value' => $diffStayOrderTotalValue, 'url' => 'javascript:void(0)'),
            array('title' => "未发货", 'front_value' => $deliverOrderFrontTotal, 'to_value' => $deliverOrderToTotal, 'diff_value' => $diffDeliverOrderTotalValue, 'url' => 'javascript:void(0)'),
            array('title' => "已发货", 'front_value' => $shippedOrderFrontTotal, 'to_value' => $shippedOrderToTotal, 'diff_value' => $diffShippedOrderTotalValue, 'url' => 'javascript:void(0)'),
            array('title' => "无效单", 'front_value' => $canceledOrderFrontTotal, 'to_value' => $canceledOrderToTotal, 'diff_value' => $diffCanceledOrderTotalValue, 'url' => 'javascript:void(0)'),
        );
        $this->assign('list_front_to_value', $_list_front_to_value);



        //start 商家
        $distributorInfoList = $this->distributorInfoService()->distributorInfoList(array(), array('distributor_id', 'distributor_name'));
        $this->assign('distributorInfoList', $distributorInfoList);
        //end 商家

        $this->assign('page_title', '订单分析');
        $this->display();
    }

    //订单 标准订单列表
    public function ordertypelistAction()/*NoPurview*/
    {
        $content = I('content');//
        $start_price = I('start_price');//
        $end_price = I('end_price');//
        $to_start_time = I('to_start_time');//后日期 开始时间
        $to_end_time = I('to_end_time');//后日期 结束时间
        $region_code = I('region_code');//
        $shop_id = I('shop_id');//
        $province = I('province');//
        $city = I('city');//
        $province_city_area = I('province_city_area');//
        $this->assign('content', $content);
        $this->assign('start_price', $start_price);
        $this->assign('end_price', $end_price);
        $this->assign('to_start_time', $to_start_time);
        $this->assign('to_end_time', $to_end_time);
        $this->assign('region_code', $region_code);
        $this->assign('shop_id', $shop_id);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('province_city_area', $province_city_area);
        $page = intval(I('p')) > 0 ? intval(I('p')) : 1;
        $pagesize = $this->pagesize;
        $result = $this->orderService()->orderTypeList($content, $start_price, $end_price, $to_start_time, $to_end_time, $region_code, $shop_id, $page, $pagesize);
        $this->assign('list', $result['list']);
        $this->assign('count', $result['count']);
        if (IS_AJAX) {
            if (empty($result['list'])) {
                $clist = '';
            } else {
                $clist = $this->renderPartial('_orderlist');
            }
            $this->ajaxReturn(array('html' => $clist, 'p' => $page + 1));
        }

        //start 商家
        $distributorInfoList = $this->distributorInfoService()->distributorInfoList(array(), array('distributor_id', 'distributor_name'));
        $this->assign('distributorInfoList', $distributorInfoList);
        //end 商家

        $this->assign('page_title', '标准订单列表');
        $this->display();
    }


    //订单 订单详情
    public function orderdetailAction()/*NoPurview*/
    {
        $order_id = I('get.order_id');
        if(empty($order_id)) $this->error("发生致命错误");
        $orderFind = $this->orderService()->orderFindService($order_id);
        if(empty($orderFind)) $this->error("发生致命错误");
        $this->assign('orderFind', $orderFind);
        $this->assign('page_title', '订单详情');
        $this->display();
    }

    private function orderService()
    {
        return D('Order', 'Service');
    }

    private function distributorInfoService()
    {
        return D('DistributorInfo', 'Service');
    }
}