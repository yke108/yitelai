<?php
namespace Common\Service;
class RegionService{
	
	//获取所有的市
	public function getAllCity($codes){
		return $this->regionDao()->getCity($codes);
	}
	
	//获取全部的省市区
	public function getAllProvinces($codes,$type=1){
		return $this->regionDao()->getAllProvinceCity($codes,$type);
	} 

	public function regionFieldList($where = array(), $field = array())
	{
		return $this->regionDao()->where($where)->field($field)->select();
	}

	public function getAllRegions($map = array()){
		return $this->regionDao()->where($map)->select();
	}
	
	public function getAllRegionsField($map = array()){
		return $this->regionDao()->where($map)->getField('region_code, region_name');
	}
	
	public function getNotRepealedRegions(){
		$map = array(
			'repealed'=>0,
		);
		return $this->regionDao()->where($map)->select();
	}
	
	public function getDistrictFullName($code){
		$list = $this->getRegionsOfProvince($code);
		if(empty($list[$code])) return '';
		
		$pcode = intval($code / 10000) * 10000;
		$str = $list[$pcode];
		$ccode = intval($code / 100) * 100;
		$str .= $list[$ccode];
		$str .= $list[$code];
		return $str;
	}
	
	public function getDistrictCityName($code){
		$list = $this->getRegionsOfProvince($code);
		if(empty($list[$code])) return '';
		
		$ccode = intval($code / 100) * 100;
		$str .= $list[$ccode];
		return $str;
	}
	
	//getDistrictFullName扩展 可以在省市区中添加一个分隔的符号
	public function getDistrictFullNameChange($code,$sign=' '){
		$list = $this->getRegionsOfProvince($code);
		if(empty($list[$code])) return '';
		
		$pcode = intval($code / 10000) * 10000;
		$str = $list[$pcode];
		$ccode = intval($code / 100) * 100;
		$ccode>$pcode && $str .= $sign.$list[$ccode];		
		$code>$ccode && $str .= $sign.$list[$code];
		return $str;
	}
	
	public function getChildList($parent_code){
		if(empty($parent_code)){
			return $this->getProvinceList();
		} elseif ($parent_code % 10000 == 0) {
			return $this->getCityListOfProvince($parent_code);
		} elseif ($parent_code % 100 == 0) {
			return $this->getDistrictOfCity($parent_code);
		} else {
			return array();
		}
	}
	
	public function getProvinceList(){
		$map = array(
			'_string'=>'region_code % 10000 = 0',
			'repealed'=>0,
		);
		return $this->regionDao()->where($map)->getField('region_code, region_name');
	}
	
	public function getCityListOfProvince($province){
		$tpl = $this->getRegionsOfProvince($province);
		$list = array();
		foreach($tpl as $ckey => $cval){
			if($ckey % 10000 == 0 || $ckey % 100 > 0) continue;
			$list[$ckey] = $cval;
		}
		return $list;
	}
	
	/*
	*	把几个地区的字段进行分割然后获取具体的地区值
	*	参数$str	字符串
	*	返回值		返回一个数组
	*/
	public function splitRegion($str){	
		$str_array=explode(',',$str);
		$new_str_array=$region_array=array();
		foreach($str_array as $key=>$val){
			if($val!=''){	
				$new_str_array[]=$val;
			}
		}
		foreach($new_str_array as $key=>$val){
			$region_array[$val]=$this->getDistrictFullNameChange($val);
		}
		return $region_array;
	}
	
	public function regionChildrenForSelect($regions){
		return $this->regionDao()->regionChildren($regions);
	}
	
	public function cityListForCheck($regions){
		return $this->regionDao()->getCityOfProvinces($regions);
	}
	
	public function districtListForCheck($regions){
		return $this->regionDao()->getDistrictOfRegions($regions);
	}
	
	public function regionList($regions){
		return $this->regionDao()->regionList($regions);
	}
	
	public function getDistrictOfCity($city){
		$tpl = $this->getRegionsOfProvince($city);
		$list = array();
		$cmax = $city + 99;
		foreach($tpl as $ckey => $cval){
			if($ckey < $city || $ckey >= $cmax) continue;
			$list[$ckey] = $cval;
		}
		return $list;
	}

	public function getRegionsOfProvince($code){
		if($code < 100000 || $code > 999999) return array();
		$proc = intval($code / 10000) * 10000;
		$min = $proc;
		$max = $proc + 10000;
		$map = array(
			'region_code'=>array(
				array('egt', $min),
				array('lt', $max),
			),
			'repealed'=>0,
		);
		return $this->regionDao()->where($map)->getField('region_code, region_name');
	}
	
	//拆分一个地区字符串，返回有多个省市区的数组
	public function getRegionArray($str){
		if(empty($str)){throw new \Exception('缺少参数');}
		$str_array=explode(',',$str);
		$return_result=$result_array=$new_str_array=$region_array=array();
		foreach($str_array as $key=>$val){
			if($val!=''){
				$val=intval($val/10000)*10000;	
				$new_str_array[]=$val;
			}
		}
		
		//处理地区数组分割成省市区
		foreach($new_str_array as $key=>$val){
			$region_array=$this->regionDao()->getProvinceAllRegion($val);
			$result_array=array();
			foreach($region_array as $key2=>$val2){
				$code1=intval($key2/10000)*10000;
				$code2=intval($key2/100)*100;
				if($code1==$key2){
					$result_array[$code1]['region_name']=$val2;
				}elseif($code2==$key2){	
					$result_array[$code1]['region_list'][$code2]['region_name']=$val2;
				}elseif($code2<$key2){
					$result_array[$code1]['region_list'][$code2]['region_list'][$key2]=$val2;	
				}
			}
			$return_result[$code1]=$result_array[$code1];
		}
		return $return_result;
		
	}
	
	/*
	*	获取省下面的所有市跟区
	*	返回一个数组
	*
	*/
	public function getProvinceLowerRegion($str){
		if(empty($str)){throw new \Exception('缺少参数');}
		$str=intval($str/10000)*10000;	
		$region_array=$this->regionDao()->getProvinceAllRegion($str);
		foreach($region_array as $key=>$val){
			$code2=intval($key/100)*100;
			if($code2==$key){
				$result_array[$code2]['region_name']=$val;
			}else{
				$result_array[$code2]['region_list'][]=$val;
			}
		}
		return $result_array;
	}
	
	//根据传进来的参数获取省份
	public function getProvinceListExtend($regions){
		if(empty($regions)){throw new \Exception('缺少参数');}
		return $this->regionDao()->regionProvinceList($this->splitCodes($regions));
	}
	
	//切割一个字符串成一个数组
	public function splitCodes($codes){
		$array=explode(',',$codes);
		foreach($array as $key=>$val){
			if($val!=''){	
				$result[]=$val;
			}
		}
		return $result;
	}
	
	public function getRegionNameCity($region_code) {
		return $this->regionDao()->getRegionNameCity($region_code);
	}

	private function regionDao(){
		return D('Region');
	}
}
