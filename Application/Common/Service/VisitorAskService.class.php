<?php
namespace Common\Service;

class VisitorAskService
{
    private $domain_name_url = null;

    public function __construct()
    {
        $this->visitor_ask = D('VisitorAsk');
    }

    /**
     * 查询前一个搜索结果
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $shop_id
     * @return array
     */
    public function visitorAskFrontCount($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
    {
        $where = array();
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($shop_id) {
            $where['shop_id'] = $shop_id;
        }
        $frontStartEntTime = frontStartEntTime($startTime, $endTime);
        $where['inputtime'] = array('between', $frontStartEntTime['starTime'] . ',' . $frontStartEntTime['endTime']);
        $count = $this->visitor_ask->where($where)->field(array('visitor_ask_id'))->count();
        return array(
            "count" => $count,
        );
    }

    /**
     * 查询后一个搜索结果
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $shop_id
     * @return array
     */
    public function visitorAskToCount($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
    {
        $where = array();
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($shop_id) {
            $where['shop_id'] = $shop_id;
        }
        $toStartEntTime = toStartEntTime($startTime, $endTime);
        $where['inputtime'] = array('between', $toStartEntTime['starTime'] . ',' . $toStartEntTime['endTime']);
        $count = $this->visitor_ask->where($where)->field(array('visitor_ask_id'))->count();
        return array(
            "count" => $count,
        );
    }

    //所有访客
    public function pageVisitorAskList($params, $where = array())
    {
        $field = array();
        $orderBy = array();
        $count = $this->visitor_ask->where($where)->count();
        $data = $this->visitor_ask->where($where)->field($field)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
        $_list = array();

        foreach ($data as $key => $val) {
            $_t = $val;
            $userFind = D('UserInfo')->where(array('user_id' => $val['user_id']))->field(array('nick_name','real_name','user_img','headimgurl'))->find();
            $regionFind = D('Region')->where(array('region_code' => $val['region_code']))->field(array('region_name'))->find();
            $_t['region_name'] = $regionFind['region_name'];
            if($userFind['nick_name']){
                $_t['name'] = $userFind['nick_name'];
            } else {
                $_t['name'] = $userFind['real_name'];
            }
            if($userFind['user_img']){
                $_t['user_img'] = domain_name_url.'/upload/'.$userFind['user_img'];
            } else {
                if($userFind['headimgurl']){
                    $_t['user_img'] = $userFind['headimgurl'];
                } else {
                    $_t['user_img'] = domain_name_url.'/public/main/images/user_default_img.jpg';
                }
            }
            $_t['inputtime'] = date('Y-m-d', $val['inputtime']);
            $_t['detailUrl'] = U('/analysis/platform/visitsdetail', array('visitor_ask_id' => $val['visitor_ask_id']));
            $_list[] = $_t;
        }
        return array(
            'list' => $_list,
            'count' => $count,
        );
    }


    public function visitorAskDetail($visitor_ask_id)
    {
        $where = array();
        $where['visitor_ask_id'] = $visitor_ask_id;
        $visitorAskFind = $this->visitor_ask->where($where)->find();
        $userFind = D('UserInfo')->where(array('user_id' => $visitorAskFind['user_id']))->field(array('nick_name','rank_id','real_name','user_img','headimgurl'))->find();
        if($userFind['nick_name']){
            $visitorAskFind['name'] = $userFind['nick_name'];
        } else {
            $visitorAskFind['name'] = $userFind['real_name'];
        }
        if($userFind['user_img']){
            $visitorAskFind['user_img'] = domain_name_url.'/upload/'.$userFind['user_img'];
        } else {
            if($userFind['headimgurl']){
                $visitorAskFind['user_img'] = $userFind['headimgurl'];
            } else {
                $visitorAskFind['user_img'] = domain_name_url.'/public/main/images/user_default_img.jpg';
            }
        }
        $visitorAskFind['rank_id'] = $userFind['rank_id'];
        $regionFind = D('Region')->where(array('region_code' => $visitorAskFind['region_code']))->field(array('region_name'))->find();
        $visitorAskFind['region_name'] = $regionFind['region_name'];
        return $visitorAskFind;
    }

}