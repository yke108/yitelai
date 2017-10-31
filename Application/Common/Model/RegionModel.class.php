<?php
namespace Common\Model;
use Think\Model;

class RegionModel extends Model{
	
	public function getFieldRecord($map,$field){
		return $this->where($map)->getField($field);
	}
	
	public function getIdsRecord($ids){
		if (!is_array($ids)) $ids = array($ids);
		$map = array(
			'region_code'=>array('in', $ids),
		);
		$list=$this->where($map)->getField('region_code, region_name');
	}
	
	public function regionChildren($regions){
		if(!is_array($regions) || count($regions) < 1) return array();
		$cond = array();
		foreach ($regions as $vo) {
			$min = $vo;
			if($vo % 10000 == 0) $max = $vo + 10000;
			elseif($vo % 100 == 0) $max = $vo + 100;
			else $max = $vo + 1;
			$cond[] = '(region_code >= "'.$min.'" and region_code < "'.$max.'")';
		}
		$map = array(
			'_string'=>implode(' OR ', $cond),
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	
	
	public function getCityOfProvince($code){
		if($code % 10000 != 0) return array();
		$map = array(
			'region_code'=>array(
				array('gt', $code),
				array('lt', $code + 10000),
			),
			'repealed'=>0,
			'_string'=>'region_code % 100 = 0'
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	public function getCityOfProvinces($regions){
		if(!is_array($regions) || count($regions) < 1) return array();
		$cond = array();
		foreach ($regions as $code){
			if($code % 10000 != 0) continue;
			$min = $code;
			$max = $code + 10000;
			$cond[] = '(region_code > "'.$min.'" and region_code < "'.$max.'")';
		}
		$map = array(
			'repealed'=>0,
			'_string'=>'('.implode(' OR ', $cond).') AND region_code % 100 = 0'
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	public function getDistrictOfRegions($regions){
		if(!is_array($regions) || count($regions) < 1) return array();
		$cond = array();
		foreach ($regions as $code){
			if($code % 100 != 0) continue;
			$min = $code;
			$max = $code % 10000 == 0 ? $code + 10000 : $code + 100;
			$cond[] = '(region_code > "'.$min.'" and region_code < "'.$max.'")';
		}
		
		$map = array(
			'repealed'=>0,
			'_string'=>'('.implode(' OR ', $cond).') AND region_code % 100 > 0'
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	public function getDistrictOfProvince($code){
		$region_code = intval($code/10000)*10000;
		$map = array(
				'region_code'=>$region_code,
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	public function getDistrictOfCity($code){
		$region_code = intval($code/100)*100;
		$map = array(
				'region_code'=>$region_code,
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	//获取省市区
	public function getRegionName($code){
		$map = array(
			'region_code'=>$code,
		);
		$district = $this->where($map)->field('region_name')->find();
		$city = $this->getDistrictOfCity($code);
		$province = $this->getDistrictOfProvince($code);
		return current($province).current($city).current($district);
	}
	
	public function getRegionNameTwo($code){
		$map = array(
				'region_code'=>$code,
		);
		$district = $this->where($map)->field('region_name')->find();
		$city = $this->getDistrictOfCity($code);
		$province = $this->getDistrictOfProvince($code);
		return current($province).' '.current($city).' '.current($district);
	}
	
	public function getRegionNameCity($code){
		$map = array(
				'region_code'=>$code,
		);
		$city = $this->getDistrictOfCity($code);
		$province = $this->getDistrictOfProvince($code);
		return current($province).' '.current($city);
	}
	
	public function getRegionNameDistrict($code){
		$map = array(
				'region_code'=>$code,
		);
		$city = $this->getDistrictOfCity($code);
		$district = $this->where($map)->field('region_name')->find();
		return current($city).current($district);
	}
	
	//获取多个完整的地区名称
	public function getAllProvinceCity($codes,$type=1){
		$type_array=array(
							1=>'getRegionName',//获取省市区
							2=>'getProvinceCity',//获取省市
						);
		if(empty($codes)){return array();}
		if(empty($type_array[$type])){return array();}
		if(is_array($codes)){
			foreach($codes as $key=>$val){
				$result[$val]=$this->$type_array[$type]($val);
			}
		}else{
			$result[$codes]=$this->$type_array[$type]($codes);
		}
		return $result;
	}
	
	//获取省市
	public function getProvinceCity($code){
		$map = array(
				'region_code'=>$code,
		);
		$district = $this->where($map)->field('region_name')->find();
		$city = $this->getDistrictOfCity($code);
		$province = $this->getDistrictOfProvince($code);
		return current($province).' '.current($city);
	}
	
	//获取市
	public function getCity($codes){
		if(empty($codes)){return array();}
		if(is_array($codes)){
			foreach($codes as $key=>$val){
				$codes[$key]=intval($val/100)*100;
			}
			$map=array('region_code'=>array('in',$codes));
			$city = $this->where($map)->getField('region_code,region_name');
			
		}else{
			$map=array('region_code'=>$codes);
			$city = $this->where($map)->getField('region_name');
		}
		
		return $city;
		
	}
	
	
	public function regionList($regions){
		if(!is_array($regions) || count($regions) < 1) return array();
		$map = array(
			'region_code'=>array('in', $regions),
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	//获取传进来所有的省份
	public function regionProvinceList($regions){
		if(!is_array($regions) || count($regions) < 1) return array();
		foreach($regions as $key=>$val){
			$code_array[]=intval($val/10000)*10000;
		}
		$map = array(
			'region_code'=>array('in', $code_array),
		);
		return $this->where($map)->getField('region_code, region_name');
	}
	
	//获取省及下面所有的市跟区
	public function getProvinceAllRegion($code){
		//if($code % 100 != 0) continue;
		$min = intval($code/10000)*10000;
		$max = $min+9999;
		$map['_string']=" region_code>=$min and region_code<=$max";
		return $this->where($map)->getField('region_code, region_name');
	}
	
}