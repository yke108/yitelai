<?php
namespace Common\Model\Goods;
use Think\Model\RelationModel;

class GoodsCommentModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'goods_comment';
    protected $pk = 'comment_id';
    
    protected $_validate = array(
    		array('distributor_id','require','分销商ID不能为空',1), //默认情况下用正则进行验证
    		array('order_goods_id','require','订单商品ID不能为空',1), //默认情况下用正则进行验证
    		array('goods_id','require','商品ID不能为空',1), //默认情况下用正则进行验证
    		array('user_id','require','用户ID不能为空',1), //默认情况下用正则进行验证
    		array('content','require','评论内容不能为空',1), //默认情况下用正则进行验证
    		array('pictures','require','晒图不能为空',1), //默认情况下用正则进行验证
    		array('stars','require','评星不能为空',1), //默认情况下用正则进行验证
    		array('stars',array(1,2,3,4,5),'评星数值为1-5星',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		//array('type_name','','名称已存在',0,'unique',3), // 验证name字段是否唯一
    		//array('repassword','password','确认密码不正确',0,'confirm'), // 验证确认密码是否和密码一致
    		//array('password','checkPwd','密码格式不正确',0,'function'), // 自定义函数验证密码格式
    );
    
    protected $_auto = array (
    		array('status','1'),  // 新增的时候把status字段设置为1
    		//array('password','md5',3,'function') , // 对password字段在新增和编辑的时候使md5函数处理
    		//array('name','getName',3,'callback'), // 对name字段在新增和编辑的时候回调getName方法
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对add_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($ids){
		return $this->where(array('type_id'=>array('in',$ids)))->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->getField('type_id, type_name');
    }
}