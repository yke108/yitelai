<?php
namespace Common\Service;

use Common\Basic\Status;
use Common\Basic\MessageConst;

class DistributorInfoService
{
    protected $DistributorInfoDao;


    public function __construct()
    {
        $this->DistributorInfoDao = D('DistributorInfo');
    }


    public function getServiceList($where = array(), $field = array('distributor_id','distributor_name','region_code'))
    {
        return $this->DistributorInfoDao->where($where)->field($field)->select();
    }

    public function getServiceFind($where = array(), $field = array('distributor_id','distributor_name'))
    {
        return $this->DistributorInfoDao->where($where)->field($field)->find();
    }

    public function pageDistributorInfoList($content, $params)
    {
        $where = array();
        if($content){
            $where['distributor_name'] = array('like','%'.$content.'%');
        }
        $field = array('distributor_id','distributor_image','distributor_name');
        $orderBy = array();
        $count = $this->DistributorInfoDao->where($where)->count();
        $data = $this->DistributorInfoDao->where($where)->field($field)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
        $_list = array();
        foreach ($data as $key => $val) {
            $_t = $val;
            $oderCount = D('OrderInfo')->field(array('order_id'))->where(array('distributor_id' => $val['distributor_id'],'pay_status' => array('in','1,2')))->count();
            if($val['distributor_image']){
                $_t['distributor_image'] = domain_name_url.'/upload/'.$val['distributor_image'];
            } else {
                $_t['distributor_image'] = domain_name_url.'/public/main/images/user_default_img.jpg';
            }
            $_t['oderCount'] = $oderCount;
            $_t['detailUrl'] = U('/Statistics/Sale/detail', array('distributor_id' => $val['distributor_id']));
            $_list[] = $_t;
        }
        return array(
            'list' => $_list,
            'count' => $count,
        );
    }

    public function designerDistributorInfoList($content, $params)
    {
        $where = array();
        if($content){
            $where['distributor_name'] = array('like','%'.$content.'%');
        }
        $field = array('distributor_id','distributor_image','distributor_name');
        $orderBy = array();
        $count = $this->DistributorInfoDao->where($where)->count();
        $data = $this->DistributorInfoDao->where($where)->field($field)->order($orderBy)->page($params['page'], $params['pagesize'])->select();
        $_list = array();
        foreach ($data as $key => $val) {
            $_t = $val;
            $designerCount = D('DesignerInfo')->field(array('designer_id'))->where(array('distributor_id' => $val['distributor_id']))->count();
            if($val['distributor_image']){
                $_t['distributor_image'] = domain_name_url.'/upload/'.$val['distributor_image'];
            } else {
                $_t['distributor_image'] = domain_name_url.'/public/main/images/user_default_img.jpg';
            }
            $_t['designerCount'] = $designerCount;
            $_t['detailUrl'] = U('/Statistics/Designer/lists', array('distributor_id' => $val['distributor_id']));
            $_list[] = $_t;
        }
        return array(
            'list' => $_list,
            'count' => $count,
        );
    }

    public function distributorInfoList($where = array(), $field = array())
    {
        return $this->DistributorInfoDao->where($where)->field($field)->select();
    }

    public function distributorInfoFind($distributor_id){
        $where = array();
        $field = array('distributor_id','distributor_image','distributor_name','region_code','distributor_tel');
        if($distributor_id){
            $where['distributor_id'] = $distributor_id;
        }
        $data = $this->DistributorInfoDao->where($where)->field($field)->find();
        if($data['distributor_image']){
            $data['distributor_image'] = domain_name_url.'/upload/'.$data['distributor_image'];
        } else {
            $data['distributor_image'] = domain_name_url.'/public/main/images/user_default_img.jpg';
        }
        $regionFind = D('Region')->where(array('region_code' => $data['region_code']))->field(array('region_name'))->find();
        $data['region_name'] = $regionFind['region_name'];
        return $data;
    }

    /**
     * 前日期 店铺统计
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $status
     * @return mixed
     */
    public function totalFrontDistributorInfo($startTime = '', $endTime = '', $region_code = '', $status = '')
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
        if ($status) {
            $where['status'] = $status;
        }
        $where['add_time'] = array('between', $frontStarTime . ',' . $frontEndTime);
        $field = array('distributor_id');
        return $this->DistributorInfoDao->where($where)->field($field)->count();
    }

    /**
     * 后日期 店铺统计
     * @param string $startTime
     * @param string $endTime
     * @param string $region_code
     * @param string $status
     * @return mixed
     */
    public function totalToDistributorInfo($startTime = '', $endTime = '', $region_code = '', $status = '')
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
        if ($status) {
            $where['status'] = $status;
        }
        $where['add_time'] = array('between', $toStarTime . ',' . $toEndTime);
        $field = array('distributor_id');
        return $this->DistributorInfoDao->where($where)->field($field)->count();
    }

}