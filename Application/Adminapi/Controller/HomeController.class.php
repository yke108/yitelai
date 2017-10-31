<?php
namespace Adminapi\Controller;

use Common\Controller\AdminapiController as FController;
use Common\Basic\SystemConst;

class HomeController extends FController
{
//    public function _initialize(){
//
//    }
    public function indexAction(){
        $post = I('post.');
        $params = array(
            'admin_id'=> $post['admin_id'],
        );
        $adminFind = D('Admin')->where(array($params))->field(array('sys_id','org_id'))->find();
        if($adminFind['sys_id'] == 2){
            $org_id = $adminFind['org_id'];
        } else {
            $org_id = '';
        }

        $userWhere = array();
        $TodayUserWhere = array();
        $YesterdayUserWhere = array();
        $orderWhere = array();
        $TodayOrderWhere = array();
        $YesterdayOrderWhere = array();
        $totalAskWhere = array();
        $TodayTotalAskWhere = array();
        $YesterdayTotalAskWhere = array();
        $orderWhere['pay_status'] = 1;
        $TodayOrderWhere['pay_status'] = 1;
        $YesterdayOrderWhere['pay_status'] = 1;

        //今天
        $toStarTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 00:00:01'))));
        $toEndTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 23:59:59'))));

        //昨天
        $frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 00:00:01')) - 86400)));
        $frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 23:59:59')) - 86400)));

        if($org_id){
            $orderWhere['distributor_id'] = $org_id;
            $TodayOrderWhere['distributor_id'] = $org_id;
            $YesterdayOrderWhere['distributor_id'] = $org_id;
            $totalAskWhere['shop_id'] = $org_id;
            $TodayTotalAskWhere['shop_id'] = $org_id;
            $YesterdayTotalAskWhere['shop_id'] = $org_id;
            $userWhere['distributor_id'] = $org_id;
            $TodayUserWhere['distributor_id'] = $org_id;
        }

        $TodayUserWhere['reg_time'] = array('between', $toStarTime . ',' . $toEndTime);
        $YesterdayUserWhere['reg_time'] = array('between', $frontStarTime . ',' . $frontEndTime);

        $TodayOrderWhere['pay_time'] = array('between', $toStarTime . ',' . $toEndTime);
        $YesterdayOrderWhere['pay_time'] = array('between', $frontStarTime . ',' . $frontEndTime);

        $TodayTotalAskWhere['inputtime'] = array('between', $toStarTime . ',' . $toEndTime);
        $YesterdayTotalAskWhere['inputtime'] = array('between', $frontStarTime . ',' . $frontEndTime);
        $data = array(
            array(
                'Title' => '总订单数',
                'Value' => $this->orderService()->appOrderTotal($orderWhere),
                'isShow' => '1',
            ),
            array(
                'Title' => '总会员数',
                'Value' => $this->userService()->userTotalCount($userWhere),
                'isShow' => '1',
            ),
            array(
                'Title' => '总访问量',
                'Value' => $this->totalAskService()->totalAskCount($totalAskWhere),
                'isShow' => '1',
            ),
            array(
                'Title' => '总成交金额',
                'Value' => $this->orderService()->appOrderTotalAmount($orderWhere),
                'isShow' => '1',
            ),
            array(
                'Title' => '今日订单数',
                'Value' => $this->orderService()->appOrderTotal($TodayOrderWhere),
                'isShow' => '1',
            ),
        	array(
        		'Title' => '今日成交金额',
        		'Value' => $this->orderService()->appOrderTotalAmount($TodayOrderWhere),
        		'isShow' => '1',
        	),
            array(
                'Title' => '今日UV', //新增会员数
                'Value' => $this->userService()->userTotalCount($TodayUserWhere),
                'isShow' => '1',
            ),
            array(
                'Title' => '今日PV', //访问量
                'Value' => $this->totalAskService()->totalAskCount($TodayTotalAskWhere),
                'isShow' => '1',
            ),
        	array(
        		'Title' => '今日转化率', //访问量
        		'Value' => '0%', //TODO
        		'isShow' => '1',
        	),
        	//TODO
        	array(
        		'Title' => '未发货',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '未付款',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '待签收',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '已测量',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '未测量',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '已安装',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '未安装',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '投诉订单',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '退款订单',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '退货订单',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '已结算',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '未结算',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '新增会员',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '兑换订单',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '佣金提单',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '罚款店铺',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '通知数',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        	array(
        		'Title' => '申请数',
        		'Value' => '0',
        		'isShow' => '1',
        	),
        );
        $datas['List'] = array_values($data);
        $this->jsonReturn($datas);
    }

    protected function adminService(){
        return D('Admin', 'Service');
    }

    private function orderService(){
        return D('Order', 'Service');
    }

    private function userService(){
        return D('User', 'Service');
    }

    private function totalAskService(){
        return D('TotalAsk', 'Service');
    }
}