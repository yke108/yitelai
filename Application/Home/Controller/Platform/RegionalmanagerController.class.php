<?php
namespace Home\Controller\Platform;

use Common\Basic\User;
use Home\Controller\BaseController;
use Common\Basic\Genre;

class RegionalmanagerController extends BaseController
{
    public function _initialize()
    {
        //$this->purviewCheck(false);
        parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }


    public function indexAction()/*人员管理*/
    {
        $info = I('');
        $this->assign('info', $info);
        $params = array(
            'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
            'pagesize' => $this->pagesize,
        );
        $content = $info['content'];
        $user_type = $info['user_type'];
        if($user_type){
            if(in_array($user_type, array(0,1,2,3,4,5))){
                $user_type_id = $user_type;
                $page_title = User::$user_type[$user_type_id];
            } else {
                $user_type_id = '';
                $page_title = '人员管理';
            }
        } else {
            $user_type_id = '';
            $page_title = '人员管理';
        }
        $where = array();
        $where['content'] = $content;
        $where['user_type'] = $user_type_id;
        $result = $this->UserInfoService()->userInfoListService($where, $params);
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
        $this->assign('page_title', $page_title);
        $this->display();
    }


    private function UserInfoService()
    {
        return D('User', 'Service');
    }
}