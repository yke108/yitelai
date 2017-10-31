<?php
namespace Common\Service;

class TouristAskService
{
    private $tourist_ask = null;

    public function __construct()
    {
        $this->tourist_ask = D('TouristAsk');
    }

    /**
     * 查询前一个搜索结果
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $shop_id
     * @return array
     */
    public function touristAskFrontList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
    {
        $where = array();
        if ($startTime && $endTime) {
            $frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
            $frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
        } else {
            $frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 00:00:01')) - 86400)));
            $frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 23:59:59')) - 86400)));
        }
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($shop_id) {
            $where['shop_id'] = $shop_id;
        }
        $where['inputtime'] = array('between', $frontStarTime . ',' . $frontEndTime);
        $count = 0;
        $data = $this->tourist_ask->where($where)->field(array('tourist_ask_id'))->select();
        $count = count($data);
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
    public function touristAskToList($startTime = '', $endTime = '', $region_code = '', $shop_id = '')
    {

        $where = array();
        if ($startTime && $endTime) {
            $toStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
            $toEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
        } else {
            $toStarTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 00:00:01'))));
            $toEndTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 23:59:59'))));
        }
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($shop_id) {
            $where['shop_id'] = $shop_id;
        }
        $where['inputtime'] = array('between', $toStarTime . ',' . $toEndTime);
        $count = 0;
        $data = $this->tourist_ask->where($where)->field(array('tourist_ask_id'))->select();
        $count = count($data);
        return array(
            "count" => $count,
        );
    }
}