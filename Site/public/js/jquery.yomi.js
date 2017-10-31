(function($){
	$.fn.yomi=function(){
		var data="";
		var _DOM=null;
		var TIMER;
		createdom =function(dom){
			_DOM=dom;
			data=$(dom).attr("data");
			data = data.replace(/-/g,"/");
			data = Math.round((new Date(data)).getTime()/1000);
			$(_DOM).append("仅剩：<em class='yomiday'></em><em class='split'>天</em><em class='yomihour'></em><em class='split'>时</em><em class='yomimin'></em><em class='split'>分</em><em class='yomisec'></em><em class='split'>秒</em>")
			reflash();
		};
		reflash=function(){
			var	range  	= data-Math.round((new Date()).getTime()/1000),
						secday = 86400, sechour = 3600,
						days 	= parseInt(range/secday),
						hours	= parseInt((range%secday)/sechour),
						min		= parseInt(((range%secday)%sechour)/60),
						sec		= ((range%secday)%sechour)%60;
			$(_DOM).find(".yomiday").html(nol(days));
			$(_DOM).find(".yomihour").html(nol(hours));
			$(_DOM).find(".yomimin").html(nol(min));
			$(_DOM).find(".yomisec").html(nol(sec));
			
			if(range>0){
				return nol(days)+' '+nol(hours)+' '+nol(min)+' '+nol(sec);
			}else{
				clearTimeout(TIMER);
				$(_DOM).html("<em class='end' style='color:#fe595a;'>已结束</em>");
			}
	
		};
		TIMER = setInterval( reflash,1000 );
		nol = function(h){
			return h>9?h:'0'+h;
		}
		return this.each(function(){
			var $box = $(this);
			createdom($box);
		});
	}
})(jQuery);


$(function(){
	$(".yomibox").each(function(){
		$(this).yomi();
	});	
});