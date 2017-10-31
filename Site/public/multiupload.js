(function($, K){
	$.hyMultiImage = {
		baseUrl:'',
		uploadUrl:'',
		show:function(obj, data, no_close_link){
			if(data == undefined || data.length < 1) return;
			var fp_input = obj.next();
			var fp_input_val = fp_input.val();
			if(fp_input_val.length < 1) fp_input_val = ',';
			var t = fp_input_val + data + ',';
			fp_input.val(t);
			
			var ref_obj = obj.parent();
			var obj_new = $('<div></div>')
			.css({'width':'130px','height':'130px','border':'1px solid #CCC','padding':'5px','overflow':'hidden','float':'left','margin-right':'10px','margin-bottom':'10px','position':'relative'})
			.insertBefore(ref_obj);
			var img = $('<img/>').attr('src', this.baseUrl+'/'+data).bind('load',function(){
				var w = $(this).width();
				var h = $(this).height();
				if(w > h){
					$(this).css({'height':'100%'});
				} else {
					$(this).css({'width':'100%'});
				}
			}).appendTo(obj_new);
			
			$('<a class="btn btn-danger" href="'+data+'">X</a>')
			.css({'position':'absolute', 'bottom':'1px', 'right':'1px', 'font-size':'12px', 'padding':'3px 5px'})
			.appendTo(obj_new).bind('click',function(){
				var ci = $(this).attr('href');
		        var flashpic = $(this).parent().parent().find('.hy_multi_image_list');
		       // alert($(this).parent().parent().html());
		        var a=flashpic.val().replace(','+ci,'');
				flashpic.val(a);
				var pobj = $(this).parent().remove();
				return false;
			});
		} //end function show
	}

	$.fn.extend({
		hyMultiUpload:function(){
			if($.hyMultiImage.editor == undefined){
				$.hyMultiImage.editor = K.editor({
					uploadJson: $.hyMultiImage.uploadUrl,
					allowFileManager: true
				});
			}
			this.each(function(){
				var o = $(this);
				if(o.attr('hy_done') == 1) return;
				o.css({'width':'118px','height':'118px','border':'1px solid #CCC','overflow':'hidden','position':'relative','background':'#dfdfdf','text-align':'center','cursor':'pointer'});
				o.bind('click', function(){
					$.hyMultiImage.editor.loadPlugin('multiimage', function() {
						$.hyMultiImage.editor.plugin.multiImageDialog({
							clickFn : function(urlList) {
								K.each(urlList, function(i, data) {
									$.hyMultiImage.show(o, data.short_url);
								});
								$.hyMultiImage.editor.hideDialog();
							}
						});
					});
				});
				var ostyle = {
//					width: (o.width() + pbw) + 'px',
//					height: (o.height() + pbw) + 'px',
//					padding:wx.pw+'px',
//					border:wx.bw+'px solid #ccc',
//					float:'left',
//					margin:'0 '+wx.mw+'px '+wx.mw+'px 0',
//					position:'relative',
//					background:wx.pw > 0 ? 'white' : '#EFEFEF',
					'padding':'5px',
					'background':'#fff',
					'border':'1px solid #dfdfdf',
					'box-sizing':'border-box',
					'float':'left',
				};
				var wrp = $('<div></div>').css(ostyle);
				o.wrap(wrp);
				var field = o.attr('hy_field');
				if(field) $('<input type="hidden"/>').attr('name', field).addClass('hy_multi_image_list').insertAfter(o);

				var src = o.attr('src');
				if(src != undefined && src != ''){
					var al = src.split(',');
					for(var sk in al){
						var sv = al[sk];
						if(sv.length < 1) continue;
						$.hyMultiImage.show(o, sv);
					}
				}
				o.attr('hy_done', 1);
			});
		},
	})
})(jQuery, KindEditor);