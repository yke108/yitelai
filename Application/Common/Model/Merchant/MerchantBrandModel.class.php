<?php
namespace Common\Model\Merchant;
use Think\Model\RelationModel;

class MerchantBrandModel extends RelationModel {
    protected $tableName = 'merchant_brand';
    
    protected $_validate = array(
    		array('brand_name','require','品牌中文名不能为空'),
    		array('en_name','require','品牌英文名不能为空'),
    		array('register_person','require','品牌商标注册人不能为空'),
    		array('register_no','require','品牌商标注册号不能为空'),
    		array('register_type','require','品牌商标类别不能为空'),
    		array('manage_type','require','经营类型不能为空'),
    		array('brand_logo','require','品牌LOGO不能为空'),
    		//array('trademark_period','require','商标注册证书有效期不能为空'),
    		array('trademark_period_start','require','商标注册证书有效期不能为空'),
    		array('trademark_period_end','require','商标注册证书有效期不能为空'),
    		array('trademark_pic','require','商标注册证书图片不能为空'),
    		//array('certificate_period','require','销售授权书有效期不能为空'),
    		array('certificate_period_start','require','销售授权书有效期不能为空'),
    		array('certificate_period_end','require','销售授权书有效期不能为空'),
    		array('certificate_pic','require','销售授权书图片不能为空'),
    		//array('customs_period','require','报关单类有效期不能为空'),
    		array('customs_period_start','require','报关单类有效期不能为空'),
    		array('customs_period_end','require','报关单类有效期不能为空'),
    		array('customs_pic','require','报关单类图片不能为空'),
    		//array('invoice_period','require','进货发票有效期不能为空'),
    		array('invoice_period_start','require','进货发票有效期不能为空'),
    		array('invoice_period_end','require','进货发票有效期不能为空'),
    		array('invoice_pic','require','进货发票图片不能为空'),
    		//array('protection_period','require','质检检疫报告有效期不能为空'),
    		array('protection_period_start','require','质检检疫报告有效期不能为空'),
    		array('protection_period_end','require','质检检疫报告有效期不能为空'),
    		array('protection_pic','require','质检检疫报告图片不能为空'),
    		//array('threec_period','require','3C许可证证书有效期不能为空'),
    		array('threec_period_start','require','3C许可证证书有效期不能为空'),
    		array('threec_period_end','require','3C许可证证书有效期不能为空'),
    		array('threec_pic','require','3C许可证证书图片不能为空'),
    		//array('food_period','require','食品卫流通许可证有效期不能为空'),
    		array('food_period_start','require','食品卫流通许可证有效期不能为空'),
    		array('food_period_end','require','食品卫流通许可证有效期不能为空'),
    		array('food_pic','require','食品卫流通许可证图片不能为空'),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
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
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    //
    public function searchAllRecords($map,$orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    //查询规定条数的方法
    public function shouRecords($map,$orderBy,$limit){
    	return $this->where($map)->order($orderBy)->limit($limit)->select();
    }
}