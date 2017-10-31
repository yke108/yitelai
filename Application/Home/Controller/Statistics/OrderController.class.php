<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;
use Common\Basic\Genre;
use Think\Cache\Driver\Redis;

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

    public function indexAction()/*订单统计*/
    {
        $content = I('content');
        $this->assign('content', $content);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $where = array();
        if ($content) {
            $where[] = array(
                'order_id' => array('like','%'.$content.'%'),
                'general_order_id' => array('like','%'.$content.'%'),
                '_logic' => 'or',
            );
        }
        $result = $this->orderService()->statisticsOrderList($where, $params);
        $this->assign('list', $result['list']);
        $this->assign('count', $result['count']);
        if (IS_AJAX) {
            if (empty($result['list'])) {
                $clist = '';
            } else {
                $clist = $this->renderPartial('_index');
            }
            $this->ajaxReturn(array('html' => $clist, 'p' => $params['page'] + 1));
        }
        $this->assign('page_title', '订单统计');
        $this->display();
    }

    public function detailAction()/*NoPurview*/
    {
        $order_id = I('get.order_id');
        $orderInfo = $this->orderService()->orderInfoService($order_id);
        $this->assign('orderInfo', $orderInfo);

        $userInfo = $this->userInfoService()->userInfoService($orderInfo['user_id']);
        $this->assign('userInfo', $userInfo);

        $orderGoodsListService = $this->orderService()->orderGoodsListService($order_id);
        $this->assign('orderGoodsListService', $orderGoodsListService['list']);
        $this->assign('brand_name', $orderGoodsListService['brand_name']);

        $distributorInfoFind = $this->distributorInfoService()->distributorInfoFind($orderInfo['distributor_id']);
        $this->assign('distributorInfoFind', $distributorInfoFind);

        $this->assign('order_id', $order_id);
        $this->assign('page_title', '订单统计-详情');
        $this->display();
    }

    private function distributorInfoService()
    {
        return D('DistributorInfo', 'Service');
    }

    private function userInfoService()
    {
        return D('User', 'Service');
    }

    private function orderService()
    {
        return D('Order', 'Service');
    }
}