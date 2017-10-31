<?php
namespace Common\Model\Area;

use Think\Model;

class AreaModel extends Model
{
    protected $tableName = 'area';

    public function getRecord($id)
    {
        return $this->find($id);
    }

    public function getRecords($ids)
    {
        if (!is_array($ids) || count($ids) < 1) return array();
        $map = array(
            'area_id' => array('in', $ids),
        );
        return $this->where($map)->getField('area_id,area_name,sort_order');
    }

    public function findRecord($map)
    {
        return $this->where($map)->find();
    }

    public function addRecord($data)
    {
        return $this->add($data);
    }

    public function saveRecord($data)
    {
        return $this->save($data);
    }

    public function delRecord($id)
    {
        return $this->delete($id);
    }

    public function searchRecords($map, $orderBy, $start, $limit)
    {
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }

    public function searchRecordsList($map, $orderBy, $start, $limit, $field = array())
    {
        return $this->where($map)->field($field)->order($orderBy)->page($start, $limit)->select();
    }

    public function searchRecordsCount($map)
    {
        return $this->where($map)->field(array('area_id'))->count();
    }
}