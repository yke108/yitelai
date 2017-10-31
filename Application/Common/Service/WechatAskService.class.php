<?php
namespace Common\Service;

class WechatAskService
{
    private $wechat_ask = null;

    public function __construct()
    {
        $this->wechat_ask = D('WechatAsk');
    }

    /**
     * 查询前一个搜索结果
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $shop_id
     * @return array
     */
    public function WechatAskFrontList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
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
        $count = $this->wechat_ask->where($where)->field(array('wechat_ask_id'))->count();
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
    public function WechatAskToList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
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
        $count = $this->wechat_ask->where($where)->field(array('wechat_ask_id'))->count();
        return array(
            "count" => $count,
        );
    }
}