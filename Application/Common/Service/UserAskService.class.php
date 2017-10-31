<?php
namespace Common\Service;

class UserAskService
{
    private $user_ask = null;

    public function __construct()
    {
        $this->user_ask = D('UserAsk');
    }

    /**
     * 查询前一个搜索结果
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $shop_id
     * @return array
     */
    public function userAskFrontList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
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
        $count = $this->user_ask->where($where)->field(array('user_ask_id'))->count();
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
    public function userAskToList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
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
        $count = $this->user_ask->where($where)->field(array('user_ask_id'))->count();
        return array(
            "count" => $count,
        );
    }
}