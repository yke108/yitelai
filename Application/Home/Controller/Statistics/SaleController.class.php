<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;
use Common\Basic\Genre;
use Think\Cache\Driver\Redis;

class SaleController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }

    protected function _purviewCheck(){
        $this->purviewCheck('index');
    }

    public function indexAction()/*销售统计*/
    {
        $content = I('content');
        $this->assign('content', $content);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $result = $this->distributorInfoService()->pageDistributorInfoList($content, $params);
        $this->assign('list', $result['list']);
        $this->assign('count', $result['count']);
        if (IS_AJAX) {
            if (empty($result['list'])) {
                $clist = '';
            } else {
                $clist = $this->renderPartial('_load_index');
            }
            $this->ajaxReturn(array('html' => $clist, 'p' => $params['page'] + 1));
        }
        $this->assign('page_title', '销售统计');
        $this->display();
    }

    public function detailAction()/*NoPurview*/
    {
        $distributor_id = I('get.distributor_id');
        $distributorInfoFind = $this->distributorInfoService()->distributorInfoFind($distributor_id);
        $this->assign('distributorInfoFind', $distributorInfoFind);

        //订单 总数量和总金额
        $orderTo = $this->orderService()->orderTotalSaleList($distributor_id);
        $this->assign('orderSaleCount', $orderTo['count']);
        $this->assign('orderSaleAmount', $orderTo['order_amount']);
        //订单 总数量和总金额

        //start 支付买家数
        $payBuyersAskTo = $this->payBuyersAskService()->payBuyersAskSaleList($distributor_id);
        $this->assign('payBuyersAskSaleCount', $payBuyersAskTo['count']);
        //end 支付买家数

        //start  退款数量和退款金额
        $afterSalesTo = $this->afterSalesService()->afterSalesSaleList($distributor_id);
        $this->assign('afterSalesToCount', $afterSalesTo['count']);
        $this->assign('afterSalesToAmount', $afterSalesTo['back_money']);
        //end 退款数量和退款金额

        $this->assign('page_title', '销售统计-详情');
        $this->display();
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

    private function areaService()
    {
        return D('Area', 'Service');
    }
}