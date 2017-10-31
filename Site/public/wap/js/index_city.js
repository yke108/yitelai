
var region_province=document.getElementById('province');
var region_city=document.getElementById('city');
var region_district=document.getElementById('district');
var select_province_index=select_city_index=0


function createRegionList(obj,id_obj,type){
	
	if(type==1){
		region_city.innerHTML='';
		region_district.innerHTML='';
		addOption(id_obj,'请选择省份',0);
		addOption(region_city,'请选择城市',0);
		addOption(region_district,'请选择区/县',0);
	}else if(type==2){
		region_city.innerHTML='';
		region_district.innerHTML='';
		addOption(id_obj,'请选择城市',0);
		addOption(region_district,'请选择区/县',0);
	}else if(type==3){
		region_district.innerHTML='';
		addOption(id_obj,'请选择区/县',0);
	}
	
	for(i in obj){
		addOption(id_obj,obj[i].name,obj[i].code);
	}
	
}

createRegionList(city,region_province,1);

region_province.onchange=function(i){
	if(region_province.selectedIndex==0){
		region_city.innerHTML='';
		region_district.innerHTML='';
		addOption(region_city,'请选择城市',0);
		addOption(region_district,'请选择区/县',0);
		return;
	}
	select_province_index=region_province.selectedIndex-1
	createRegionList(city[select_province_index].more,region_city,2);
}

region_city.onchange=function(i){
	if(region_city.selectedIndex==0){
		region_district.innerHTML='';
		addOption(region_district,'请选择区/县',0);
		return;
	}
	select_city_index=region_city.selectedIndex-1;
	createRegionList(city[select_province_index].more[select_city_index].more,region_district,3);
}

function addOption(obj,title,value) {
	var op=document.createElement("option");      // 新建OPTION (op) 
	op.setAttribute("value",value);          // 设置OPTION的 VALUE 
	op.appendChild(document.createTextNode(title)); // 设置OPTION的 TEXT
	obj.appendChild(op);           // 为SELECT 新建一 OPTION(op)
}




