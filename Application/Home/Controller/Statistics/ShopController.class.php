<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;
use Common\Basic\Genre;
use Think\Cache\Driver\Redis;

class ShopController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }

    //地区统计店铺数量
    public function indexAction()/*店铺统计管理*/
    {
        $area = I('get.area');
        $this->assign('area', $area);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        if ($area) {
            $params['area_name'] = $area;
        }
        $result = $this->areaService()->areaListCount($params);
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
        $this->assign('page_title', '店铺统计');
        $this->display();
    }

    public function detailAction()/*NoPurview*/
    {
        $this->assign('page_title', '店铺统计-详情');
        $this->display();
    }

    private function areaService()
    {
        return D('Area', 'Service');
    }
}