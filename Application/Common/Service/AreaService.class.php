<?php
namespace Common\Service;

use Common\Basic\CsException;

class AreaService
{
    public function getInfo($id)
    {
        if ($id < 1) return [];
        return $this->areaDao()->getRecord($id);
    }

    public function infoCreateOrModify($params)
    {
        if (empty($params['area_name'])) throw new CsException('区域名不能为空');
        $data = array(
            'cat_id' => trim($params['cat_id']),
            'area_name' => $params['area_name'],
            'add_time' => time(),
            'sort_order' => $params['sort_order'],
        );
        $areaDao = $this->areaDao();
        if ($params['area_id'] > 0) {
            $data['area_id'] = $params['area_id'];
            $result = $areaDao->saveRecord($data);
            if ($result === false) {
                throw new \Exception('修改失败');
            }
        } else {
            $result = $areaDao->addRecord($data);
            if ($result < 1) {
                throw new \Exception('添加失败');
            }
        }
    }

    public function adminSet($params)
    {
        if (empty($params['admin_id'])) throw new CsException('未指定负责人');
        if (empty($params['area_id'])) throw new CsException('区域不存在');
        $data = array(
            'area_id' => trim($params['area_id']),
            'admin_id' => $params['admin_id'],
        );
        $areaDao = $this->areaDao();
        $result = $areaDao->saveRecord($data);
        if ($result === false) {
            throw new \Exception('修改失败');
        }
    }

    public function infoDelete($id)
    {
        $result = $this->areaDao()->delRecord($id);
        if ($result === false) throw new \Exception('删除失败');
    }

    //分页查询
    public function infoPagerList($params)
    {
        $params['page'] < 1 && $params['page'] = 1;
        $params['pagesize'] < 1 && $params['pagesize'] = 20;
        $map = array();
        $areaDao = $this->areaDao();
        $count = $areaDao->searchRecordsCount($map);
        $list = array();
        if ($count > 0) {
            $orderby = empty($params['orderby']) ? 'sort_order ASC, area_id DESC' : $params['orderby'];
            $list = $areaDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
            foreach ($list as $vo) {
                $ids[$vo['admin_id']] = $vo['admin_id'];
            }
            $admins = $this->adminDao()->getRecords($ids);
        }
        return array(
            'list' => $list,
            'count' => $count,
            'pagesize' => $params['pagesize'],
            'admin_list' => $admins,
        );
    }

    public function areaListCount($params)
    {
        $where = array();
        if ($params['area_name']) {
            $where['area_name'] = array('like', '%' . $params['area_name'] . '%');
        }
        $count = $this->areaDao()->searchRecordsCount($where);
        $orderby = 'sort_order ASC, area_id DESC';
        $field = array('area_id', 'area_name');
        $data = $this->areaDao()->searchRecordsList($where, $orderby, $params['page'], $params['pagesize'], $field);
        $_list = array();
        foreach ($data as $key => $val) {
            $_t = $val;
            $_t['areaDistributor'] = D('DistributorInfo')->where(array('area_id' => $val['area_id']))->field(array('distributor_id'))->count();
            $_list[] = $_t;
        }
        return array(
            'list' => $_list,
            'count' => $count,
        );
    }

    public function getAllList()
    {
        return $this->areaDao()->field('area_id, area_name')->select();
    }

    private function areaDao()
    {
        return D('Common/Area/Area');
    }

    private function adminDao()
    {
        return D('Common/Admin/AdminInfo');
    }
}