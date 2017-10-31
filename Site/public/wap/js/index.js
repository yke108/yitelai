
var nameEl = document.getElementById('region_name');
var region_code =document.getElementById('region_code');
var region_name =document.getElementById('region_name');

var first = []; /* 省，直辖市 */
var second = []; /* 市 */
var third = []; /* 镇 */

var checked = [0, 0, 0]; /* 已选选项 */

function creatList(obj, list){
  obj.forEach(function(item, index, arr){
	  var temp = new Object();
	  temp.text = item.name;
	  temp.value = item.code;
	  list.push(temp);
  })
}

creatList(city, first);


if (city[0].hasOwnProperty('more')) {
  creatList(city[0].more, second);
} else {
  second = [{text: '', value: 0}];
}

if (city[0].more[0].hasOwnProperty('more')) {
  creatList(city[0].more[0].more, third);
} else {
  third = [{text: '', value: 0}];
}

var picker = new Picker({
	data: [first, second, third],
	selectedIndex: [0, 0, 0],
	title: '地区选择'
});

picker.on('picker.select', function (selectedVal, selectedIndex) {
  var code='';
  var text1 = first[selectedIndex[0]].text;
  var text2 = second[selectedIndex[1]].text;
  var text3 = third[selectedIndex[2]] ? third[selectedIndex[2]].text : '';
  var code1 = first[selectedIndex[0]].value;
  var code2 = second[selectedIndex[1]].value;
  var code3 = third[selectedIndex[2]] ? third[selectedIndex[2]].value : 0;
 
 if(code3>0){
	code=code3;	
 }else if(code3>0){
	code=code2;		
 }else{
	code=code1;
 }
	region_name.value=nameEl.value = text1 + ' ' + text2 + ' ' + text3;
	region_code.value=code;
	
});

picker.on('picker.change', function (index, selectedIndex) {
  if (index === 0){
    firstChange();
  } else if (index === 1) {
    secondChange();
  }

  function firstChange() {
    second = [];
    third = [];
    checked[0] = selectedIndex;
    var firstCity = city[selectedIndex];
    if (firstCity.hasOwnProperty('more')) {
      creatList(firstCity.more, second);
      
      var secondCity = city[selectedIndex].more[0]
      if (secondCity.hasOwnProperty('more')) {
        creatList(secondCity.more, third);
      } else {
        third = [{text: '', value: 0}];
        checked[2] = 0;
      }
    } else {
      second = [{text: '', value: 0}];
      third = [{text: '', value: 0}];
      checked[1] = 0;
      checked[2] = 0;
    }
    
    picker.refillColumn(1, second);
    picker.refillColumn(2, third);
    picker.scrollColumn(1, 0)
    picker.scrollColumn(2, 0)
  }

  function secondChange() {
    third = [];
    checked[1] = selectedIndex;
    var first_index = checked[0];
    if (city[first_index].more[selectedIndex].hasOwnProperty('more')) {
      var secondCity = city[first_index].more[selectedIndex];
      creatList(secondCity.more, third);
      picker.refillColumn(2, third);
      picker.scrollColumn(2, 0)
    } else {
      third = [{text: '', value: 0}];
      checked[2] = 0;
      picker.refillColumn(2, third);
      picker.scrollColumn(2, 0)
    }
  }

});

picker.on('picker.valuechange', function (selectedVal, selectedIndex) {
  console.log(selectedVal);
  console.log(selectedIndex);
});

/*nameEl.addEventListener('click', function () {
	picker.show();
});*/
region_name.addEventListener('click', function () {
	picker.show();
});