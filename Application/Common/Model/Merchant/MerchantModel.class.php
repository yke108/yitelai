<?php
namespace Common\Model\Merchant;
use Think\Model\RelationModel;

class MerchantModel extends RelationModel {
    protected $tableName = 'merchant';
    
    protected $_validate = array(
    		array('license_type','require','执照类型不能为空'),
    		array('company_name','require','公司名称不能为空'),
    		array('license_no','require','营业执照注册号不能为空'),
    		array('legal_person','require','法定代表人姓名不能为空'),
    		array('license_region','require','营业执照所在地不能为空'),
    		array('license_address','require','营业执照详细地址不能为空'),
    		array('license_date','require','成立日期不能为空'),
    		array('license_period_start','require','营业期限不能为空'),
    		array('license_period_end','require','营业期限不能为空'),
    		array('license_capital','require','注册资本不能为空'),
    		array('license_scope','require','经营范围不能为空'),
    		array('company_region','require','公司所在地不能为空'),
    		array('company_address','require','公司详细地址不能为空'),
    		array('company_tel','require','公司电话不能为空'),
    		array('company_tel','/^([0-9]{3,4}-)?[0-9]{7,8}$/','公司电话格式不正确'),
    		array('emergency_contacter','require','公司紧急联系人不能为空'),
    		array('emergency_tel','/^((\(\d{2,3}\))|(\d{3}\-))?13\d{9}$/','公司紧急联系人手机格式不正确'),
    		array('legalperson_identityno','require','法定代表人身份证号不能为空'),
    		array('legalperson_identityno','/(^\d{15}$)|(^\d{17}([0-9]|X)$)/','法定代表人身份证号格式不正确'),
    		array('legalperson_certificatepic','require','法人身份证电子版不能为空'),
    		array('license_pic','require','营业执照电子版不能为空'),
    		array('bank_licencepic','require','银行开户许可证电子版不能为空'),
    		
    		//array('organization_no','require','组织机构代码不能为空'),
    		//array('organization_period_start','require','组织机构代码有效期不能为空'),
    		//array('organization_period_end','require','组织机构代码有效期不能为空'),
    		//array('organization_pic','require','组织机构代码电证子版不能为空'),
    		
    		array('tax_type','require','纳税人类型不能为空'),
    		array('tax_no','require','税号不能为空'),
    		array('tax_code','require','纳税类型税码不能为空'),
    		array('taxno_pic','require','纳税类型税码电子版不能为空'),
    		array('taxcert_pic','require','一般纳税人资格证电子版不能为空'),
    		array('bank_accountname','require','银行开户名不能为空'),
    		array('bank_branch_no','require','开户银行支行联号不能为空'),
    		array('bank_branch_region','require','开户银行支行所在地不能为空'),
    		
    		array('company_type','require','公司类型不能为空'),
    		//array('company_site','/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/','公司官网地址格式不正确'),
    		array('company_yearamount','require','最近一年销售额不能为空'),
    		array('store_site','require','网店地址不能为空'),
    		//array('store_site','/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/','网店地址格式不正确'),
    		array('operate_num','require','网店运营人数不能为空'),
    		array('customer_peramount','require','预计平均客单价不能为空'),
    		array('store_status','require','仓库情况不能为空'),
    		array('store_address','require','仓库地址不能为空'),
    		array('express_company','require','常用物流公司不能为空'),
    		array('erp_type','require','ERP类型不能为空'),
    		array('use_ourstore','require','是否会选择谷安居仓储不能为空'),
    		array('use_ourexpress','require','是否择谷安居物流不能为空'),
    		array('business_invite','require','业务邀请不能为空'),
    		
    		array('type_id','require','期望店铺类型不能为空'),
    		array('cat_id','require','主营类目不能为空'),
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
	
	public function updateRecord($map, $data){
		$data['update_time'] = NOW_TIME;
		return $this->where($map)->save($data);
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