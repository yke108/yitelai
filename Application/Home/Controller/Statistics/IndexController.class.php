<?php
namespace Home\Controller\Statistics;

use Home\Controller\BaseController;


class IndexController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }


    public function indexAction()/*统计管理*/
    {
        $this->assign('page_title', '统计管理');
        $this->display();
    }

}