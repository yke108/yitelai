(function($, K){
	$.hySingleImage = {
		baseUrl:'',
		uploadUrl:'',
		show:function(obj, data, no_close_link){
			var wrap = obj.parent();
			wrap.children().hide();
			$('<img src="'+this.baseUrl+'/'+data+'"/>').bind('load',function(){
			//$('<img src="'+this.baseUrl+data+'"/>').bind('load',function(){
				var pw = $(this).parent().width(), ph = $(this).parent().height();
				if(pw <= 0 || ph <= 0) return;
				var w = $(this).width(), h = $(this).height();
				if(w < h * pw / ph) $(this).css({'height':'100%'});
				else $(this).css({'width':'100%'});
				var imgw = $(this).width();
				var imgh = $(this).height();
				if(imgw < pw) $(this).css('margin-left', ((pw - imgw)/ 2) + 'px');
				if(imgh < ph) $(this).css('margin-top', ((ph - imgh)/ 2) + 'px');
				$(this).show();
			}).appendTo(wrap).hide();
			wrap.parent().children(':hidden').val(data);
			if(no_close_link != 1){
				
			}//end if
		}
	}

	$.fn.extend({
		hySingleUpload:function(href, url, wx){
			if(href != undefined && href.length > 0) $.hySingleImage.uploadUrl = href;
			if(url != undefined && url.length > 0) $.hySingleImage.baseUrl = url;
			if(wx == undefined) wx = {};
			if(wx.pw == undefined) wx.pw = 5;
			if(wx.bw == undefined) wx.bw = 1;
			if(wx.mw == undefined) wx.mw = 15;
			if(wx.btn_bw == undefined) wx.btn_bw = 1;
			var pbw = (wx.pw + wx.bw) * 2;
			this.each(function(){
				var o = $(this);
				if(o.attr('hy_done') == 1) return;
				var field = o.attr('hy_field');
				if(field) $('<input type="hidden"/>').attr('name', field).insertAfter(o);
				var ostyle = {
					width: (o.width() + pbw) + 'px',
					height: (o.height() + pbw) + 'px',
					padding:wx.pw+'px',
					border:wx.bw+'px solid #ccc',
					float:'left',
					margin:'0 '+wx.mw+'px '+wx.mw+'px 0',
					position:'relative',
					background:wx.pw > 0 ? 'white' : '#EFEFEF',
					'box-sizing':'border-box'
				};
				var wrp = $('<div></div>').css(ostyle);
				o.wrap(wrp);
				var uploadbutton = KindEditor.uploadbutton({
					button : o[0],
					fieldName : 'imgFile',
					url : $.hySingleImage.uploadUrl,
					afterUpload : function(data) {
						if (data.error > 0) {
							alert(data.message);
							return;
						}
						var oNew = $(this.button);
						$.hySingleImage.show(oNew, data.short_url);
					},
					afterError : function(str) {
						alert('自定义错误信息: ' + str);
					}
				});
				uploadbutton.fileBox.change(function(e) {
					uploadbutton.submit();
				});
				ostyle = {
					width:o.width() + 'px',
					height:o.height() + 'px'
				};
				o.prev().find('form').css(ostyle);
				ostyle = {
					width:o.width() + 'px',
					height:o.height() + 'px',
					border:wx.btn_bw+'px solid #CCC',
					background:'#EFEFEF',
					display:'block'
				};
				o.prev().find('input').css(ostyle);
				
				var src = o.attr('src');
				if(src != undefined && src != ''){
					$.hySingleImage.show(o, src);
				}
				o.attr('hy_done', 1);
				
			});
		},
		hySingleImageDisplay:function(url, wx){
			$.hySingleImage.baseUrl = url;
			if(wx == undefined) wx = {};
			if(wx.pw == undefined) wx.pw = 5;
			if(wx.bw == undefined) wx.bw = 1;
			if(wx.mw == undefined) wx.mw = 15;
			if(wx.btn_bw == undefined) wx.btn_bw = 1;
			var pbw = (wx.pw + wx.bw) * 2;
			this.each(function(){
				var o = $(this);
				if(o.attr('hy_done') == 1) return;
				var ostyle = {
					width: (o.width() + pbw) + 'px',
					height: (o.height() + pbw) + 'px',
					padding:wx.pw+'px',
					border:wx.bw+'px solid #ccc',
					float:'left',
					margin:'0 '+wx.mw+'px '+wx.mw+'px 0',
					position:'relative',
					background:wx.pw > 0 ? 'white' : '#EFEFEF',
					'box-sizing':'border-box'
				};
				var wrp = $('<div></div>').css(ostyle);
				o.wrap(wrp);
				var src = o.attr('src');
				if(src != undefined && src != ''){
					$.hySingleImage.show(o, src, 1);
				}
				o.attr('hy_done', 1);
			});
		}
	})
})(jQuery, KindEditor);