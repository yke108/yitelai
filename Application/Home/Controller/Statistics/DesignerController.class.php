<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;
use Common\Basic\Genre;
use Think\Cache\Driver\Redis;

class DesignerController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }

    protected function _purviewCheck(){
        $this->purviewCheck('index');
    }

    public function indexAction()/*设计师统计*/
    {
        $content = I('content');
        $this->assign('content', $content);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $result = $this->distributorInfoService()->designerDistributorInfoList($content, $params);
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
        $this->assign('page_title', '设计师统计');
        $this->display();
    }

    public function listsAction()/*设计师列表*/
    {
        $content = I('content');
        $distributor_id = I('distributor_id');
        $this->assign('content', $content);
        $this->assign('distributor_id', $distributor_id);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $result = $this->DesignerService()->designerInfoListService($distributor_id, $content, $params);
        $this->assign('list', $result['list']);
        $this->assign('count', $result['count']);
        if (IS_AJAX) {
            if (empty($result['list'])) {
                $clist = '';
            } else {
                $clist = $this->renderPartial('_lists');
            }
            $this->ajaxReturn(array('html' => $clist, 'p' => $params['page'] + 1));
        }
        $this->assign('page_title', '设计师列表');
        $this->display();
    }

    public function designerdetailAction()/*NoPurview*/
    {
        $designer_id = I('get.designer_id');
        $designerFind = $this->DesignerService()->designerFindService($designer_id);
        $this->assign('designerFind', $designerFind);
        $this->assign('page_title', '设计师-详情');
        $this->display();
    }

    public function visitorAskService()
    {
        return D('VisitorAsk', 'Service');
    }

    public function DesignerService()
    {
        return D('Designer', 'Service');
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