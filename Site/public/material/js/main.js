//tab
function setTab(name,cursel,n){
	for(i=1;i<=n;i++){
		var menu=document.getElementById(name+i);
		var con=document.getElementById("con_"+name+"_"+i);
		menu.className=i==cursel?"hover":"";
		con.style.display=i==cursel?"block":"none";
	}
}

$(function(){
	$(".select_nav").hover(function(){
		$(this).toggleClass("on")
		$(this).find(".top_nav").toggle()
	})
	$(".page_main_list ul li").hover(function(){
		$(this).find(".down_btn").animate({top:0})
	},function(){
		$(this).find(".down_btn").animate({top:'-56px'})	
	})
	/*$(".page_main_detaild_r_1 p a.bg1").click(function(){
		$(".down_pop").show()	
		$(".mask").show()
	})*/
	
	$(".down_pop i").click(function(){
		$(".down_pop").hide()	
		$(".mask").hide()
	})
})