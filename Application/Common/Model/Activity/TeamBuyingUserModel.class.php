<?php
namespace Common\Model\Activity;
use Think\Model\RelationModel;

class TeamBuyingUserModel extends RelationModel {
    protected $tableName = 'team_buying_user';
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function getFieldRecord($map,$field='id',$page,$pagesize){
		return $this->where($map)->page($page,$pagesize)->getField($field);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        $list=$this->where($map)->order($orderBy)->page($start, $limit)->select();
		foreach($list as $key=>$val){
			$user_id[]=$val['user_id'];
		}
		if(!empty($user_id)){
			$user_list=$this->userInfoDao()->getFieldRecord(array('user_id'=>array('in',$user_id)),'user_id,nick_name,user_img,headimgurl');
			foreach($list as $key=>$val){
				$list[$key]['nick_name']=$user_list[$val['user_id']]['nick_name'];
				$list[$key]['user_img']=$user_list[$val['user_id']]['user_img'];
				$list[$key]['headimgurl']=$user_list[$val['user_id']]['headimgurl'];
			}
		}
		return $list;
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    //定义查询方法
    public function allRecords($map,$orderby){
    	return $this->where($map)->order($orderby)->select();
    }
	
	public function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}