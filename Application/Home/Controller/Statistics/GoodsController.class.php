<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;
use Common\Basic\Genre;
use Think\Cache\Driver\Redis;

class GoodsController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }

    protected function _purviewCheck(){
        $this->purviewCheck('index');
    }

    public function indexAction()/*店铺商品收藏统计*/
    {
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $where = array();
        $orderby = array('collect_count' => 'DESC');
        $result = $this->goodsService()->distributorGoodListService($where,$params, $orderby);
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
        $this->assign('page_title', '店铺商品收藏统计');
        $this->display();
    }

    private function goodsService()
    {
        return D('Goods', 'Service');
    }
}