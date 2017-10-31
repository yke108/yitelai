$.fn.hy_idsel = function(){
	var ox = $(this);
	if($(this).hasClass('hy_done')) return ox;
	$(this).addClass('hy_done');
	ox.wrap('<div></div>');
	$('<span></span>').insertBefore(ox).html(ox.attr('title'));
	$('<a></a>').insertAfter(ox).html('x').attr('href','#').bind('click',function(){
		$(this).parent().remove();
	});
	ox.parent().css('border', '1px solid #CCC');
	return ox;
}
$.fn.hy_idsel_in_iframe = function(obj){
	var that = this;
	if (obj == undefined) obj = $(parent.$('#'+window.name+'-link'));
	var objp = obj.parent();
	objp.find(".hy_idsel").each(function(){
		var val = $(this).val();
		$(that).find('input').each(function(){
            if($(this).val()==val) $(this).prop("checked", true);
        });
	});
	$(this).find('input').each(function(){
		if($(this).hasClass('hy_done')) return;
		$(this).addClass('hy_done');
		$(this).bind('click', function(){
			if($(this).prop("checked")){
				var val = $(this).val();
				var html = $(this).attr('title');
				var obj = $(parent.$('#'+window.name+'-link'));
				if($(obj).hasClass('is_radio')){
					$(obj).prevAll().remove();
				}
				$('<input/>').attr('type', 'hidden').attr('name', obj.attr('hy_field')).
				addClass('hy_idsel').attr('title', html).val(val).insertBefore(obj).hy_idsel();
				
			}else{
				var val = $(this).val();
				$(parent.$('#'+window.name+'-link')).parent().find(".hy_idsel").each(function() {
                    if($(this).val()==val) $(this).parent().remove();
                });
			}
		});
	});
	return that;
}

function run_for_del_link(obj){
	if(!confirm(obj.attr('cs_tip'))) return false;
	layer.load();
	$.post(obj.attr('href'), {}, function(res){
		if(res.status == 1){
			obj.parent().parent().remove();
    	}
		deal_after_ajax_return(obj, res);
	}, 'json');
	return false;
}

function run_for_st_single_link(obj){
	if(!confirm(obj.attr('cs_tip'))) return false;
	layer.load();
	$.post(obj.attr('href'), {}, function(res){
		if(res.status == 1){
			obj.html('');
    	}
		deal_after_ajax_return(obj, res);
	}, 'json');
	return false;
}

function run_for_page_link(obj){
	var cs_tip = obj.attr('cs_tip');
	if(cs_tip != undefined && cs_tip.length > 0){
		if(!confirm(cs_tip)) return false;
	}
	if(obj.hasClass('cs_sidebar_link')){
		$('.cs_sidebar_link').each(function(){
			$(this).parent().removeClass('active');
		});
		obj.parent().addClass('active');
	}
	if(obj.hasClass('cs_dpmenu_link')){
		$('.cs_dpmenu_link').each(function(){
			$(this).parent().removeClass('active');
		});
		obj.parent().addClass('active');
	}
	layer.load();
	$.get(obj.attr('href'), {}, function(res){
		deal_after_ajax_return(obj, res);
	}, 'json');
	return false;
}

function run_by_link(url){
	layer.load();
	$.get(url, {}, function(res){
		deal_after_ajax_return($('#cs_main_content'), res);
	}, 'json');
}

function run_for_ajax_group(obj){
	var refName = obj.attr('cs_ref_form');
	var data = $('#'+refName).serialize();
	layer.load();
	$.post(obj.attr('href'), data, function(res){
		deal_after_ajax_return(obj, res);
	}, 'json');
	return false;
}

function deal_after_ajax_return(obj, res){
	layer.closeAll('loading');
	if(res.status == 2){
		var wo = $('#cs_main_content');
		if(obj.hasClass('cs_show_modal')){
			var modal = $('#'+obj.attr('cs_id'));
			if(modal != undefined){
				var dnew = modal.modal('show').find('.modal-content').html(res.info);
			}
		} else if(obj.hasClass('hy_show_modal')){
			var w = obj.attr('hy_w');
			var h = obj.attr('hy_h');
			if(parseInt(w) < 1) w = 400;
			if(parseInt(h) < 1) h = 200;
			var mc_box = obj.parents('.cs_main_content_box');
			var modal = $('<div></div>').addClass('modal').addClass('hy_tmp');
			var modal_dialog = $('<div></div>').addClass('modal-dialog').css('width', w+'px').appendTo(modal);
			var modal_content = $('<div></div>').addClass('modal-content')
			.appendTo(modal_dialog).css('height',h+'px').css('overflow','auto')
			.html(res.info);
			if(obj.hasClass('modal_type_iframe')){
				modal_content.addClass('cs_iframe');
			}
			modal.on('hidden.bs.modal', function(){$(this).remove()});
			modal.appendTo(mc_box).modal('show');
		} else if(obj.hasClass('cs_new_con')) {
			obj.parents('.cs_main_content_box').hide();
			var dnew = $('<div></div>').addClass('cs_main_content_box');
			dnew.appendTo(wo).html(res.info);
		} else if(obj.hasClass('cs_this_box')){
			var box = obj.parents('.cs_main_content_box');
			box.html(res.info);
		} else if(obj.parents('.cs_iframe').length > 0){
			obj.parents('.cs_iframe').html(res.info);
		} else {
			var box = obj.parents('.cs_main_content_box');
			if(box.length < 1){
				wo.html('');
				var dnew = $('<div></div>').addClass('cs_main_content_box');
				dnew.appendTo(wo).html(res.info);
			} else {
				box.html(res.info);
			}
		}
		bind_after_main_content_load(dnew);
	}else if(res.status == 1){
		layer.msg(res.info);
		if(res.url != undefined && res.url.length > 1){
			if(res.no_ajax > 0) window.location.href = res.url;
			else run_by_link(res.url);
		}else if(obj.hasClass('cs_close_con')){
			var cobj = obj.parents('.cs_main_content_box');
			var pre = cobj.hide().prev();
			if(pre != undefined) pre.show();
			cobj.remove();
		}else if(obj.parents('.modal').length > 0){
			var modal = obj.parents('.modal');
			if(modal.attr('href') != undefined){
				run_for_page_link(modal);
			} else {
				var p_obj = modal.parents('.cs_main_content_box').find('.hy_page_content');
				if(p_obj != undefined && p_obj.attr('href') != undefined){
					run_for_page_link(p_obj);
				}
			}
			if(modal.hasClass('hy_tmp')) modal.modal('hide').remove();
			else modal.modal('hide');
   		}else if(obj.hasClass('cs_flesh_page')){
   			var pobj = obj.parents('.cs_flesh_info');
			if(pobj != undefined){
				run_for_page_link(pobj);
			}
   		}
	} else {
		if(res.force_redirect_page == 1 && res.url != undefined){
			window.location.href = res.url;
			return;
		}
		layer.msg(res.info, function(){});
	}
}

function run_for_select_all(obj){
	var clsName = obj.attr('cs_checkbox');
	$("."+clsName).attr("checked",true);
}

function run_for_unselect_all(obj){
	var clsName = obj.attr('cs_checkbox');
	$("."+clsName).attr("checked",false);
	return false;
}

function bind_after_main_content_load(obj){
	//确认链接
	$('.cs_del_confirm').unbind('click').bind('click',function(){
		return run_for_del_link($(this));
	});
	
	//AJAX加载页面链接
	$('.cs_ajax_link').unbind('click').bind('click',function(){
		return run_for_page_link($(this));
	});
	
	$('.cs_st_single_link').unbind('click').bind('click',function(){
		return run_for_st_single_link($(this));
	});
	
	//form提交
	$('.cs_ajax_form').unbind('submit').bind('submit', function(){
		return submit_ajax_form($(this));
	});
	
	//链接提交表单
	$('.hy_submit').unbind('click').bind('click',function(){
		return submit_form_by_link($(this));
	});
	
	//批处理
	$('.cs_ajax_group').unbind('click').bind('click', function(){
		return run_for_ajax_group($(this));
	});
	
	//全选
	$('.cs_selct_all').unbind('click').bind('click', function(){
		var ox = $(this);
		var clsName = ox.attr('cs_checkbox');
		$("."+clsName).attr("checked", ox.is(':checked'));
	});
	
	$('.cs_unselct_all').unbind('click').bind('click', function(){
		return run_for_unselect_all($(this));
	});
	
	$('.cs_close_link').unbind('click').bind('click', function(){
		var obj = $(this).parents('.cs_main_content_box');
		var pre = obj.hide().prev();
		if(pre != undefined) pre.show();
		obj.remove();
	});
	
	$('.hy_dp').each(function(){
		if($(this).hasClass('hy_done')) return;
		$(this).addClass('hy_done').datepicker({format:"yyyy-mm-dd",language: "zh-CN"});
	});
	
	/*$('.hy_upload_single').each(function(){
		$(this).hySingleUpload('', '');
	});*/
	$('.hy_upload_multi').each(function(){
		$(this).hyMultiUpload();
	});
	
	$('.hy_idsel').hy_idsel();
	$('.hy_iframe_sel').each(function(){
		if($(this).hasClass('hy_done')) return;
		$(this).addClass('hy_done');
		$(this).bind('click',function(){
			var timestamp=new Date().getTime();
			var that = $(this);
			var w = $(this).attr('hyw');
			var h = $(this).attr('hyh');
			var url = $(this).attr('href');
			if(w == undefined) w = 700;
			if(h == undefined) h = 530;
			layer.open({
			  type: 2,
			  title:'请选择',
			  area: [w+'px', h+'px'],
			  fix: false, //不固定
			  success:function(layero,index){
				that.attr('id', layero.find('iframe').attr('id') + '-link');
				layero.find('iframe').contents().find('.hy_idsel_in_iframe').hy_idsel_in_iframe(that);
				},
			  maxmin: true,
			  content:url
			});
			return false;
		});
	});
	
	$('.hy_idsel_in_iframe').hy_idsel_in_iframe();
	
	$('.ueditor').each(function(){
		var strid = $(this).attr('id');
		if(strid == undefined) return;
		var ifw = $(this).attr('w');
		if(ifw == undefined) ifw = 1000;
		UE.delEditor(strid);
		UE.getEditor(strid, {maximumWords:15000,initialFrameWidth:ifw});
	});
	
	//关闭modal
	$('.cs_close_modal').unbind('click').bind('click',function(){
		$(this).parents('.modal').modal('hide');
	});
}

function submit_ajax_form(obj){
	layer.load();
	$.ajax({
		type:obj.attr('method'),
		url:obj.attr('action'),
		dataType:'json',
		data:obj.serialize(),
		success:function(res){
			deal_after_ajax_return(obj, res);
		}
	});	
	return false;
}

function submit_form_by_link(obj){
	layer.load();
	var form = obj.parents('form');
	$.ajax({
		type:form.attr('method'),
		url:obj.attr('href'),
		dataType:'json',
		data:form.serialize(),
		success:function(res){
			deal_after_ajax_return(obj, res);
		}
	});	
	return false;
}

$(document).ready(function(){
	bind_after_main_content_load();
})

$( function() {
    $.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = $( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
 
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
 
      _createAutocomplete: function() {
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
 
        this.input = $( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left form-control" )
          .css('background', 'none')
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: $.proxy( this, "_source" )
          })
          .tooltip({
            classes: {
              "ui-tooltip": "ui-state-highlight"
            }
          });
 
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
          },
 
          autocompletechange: "_removeIfInvalid"
        });
      },
 
      _createShowAllButton: function() {
        var input = this.input,
          wasOpen = false;
 
        $( "<a>" )
          .attr( "tabIndex", -1 )
          .attr( "title", "Show All Items" )
          .tooltip()
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .on( "mousedown", function() {
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
          })
          .on( "click", function() {
            input.trigger( "focus" );
 
            // Close if already visible
            if ( wasOpen ) {
              return;
            }
 
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", "" );
          });
      },
 
      _source: function( request, response ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = $( this ).text();
          if ( this.value && ( !request.term || matcher.test(text) ) )
            return {
              label: text,
              value: text,
              option: this
            };
        }) );
      },
 
      _removeIfInvalid: function( event, ui ) {
 
        // Selected an item, nothing to do
        if ( ui.item ) {
          return;
        }
 
        // Search for a match (case-insensitive)
        var value = this.input.val(),
          valueLowerCase = value.toLowerCase(),
          valid = false;
        this.element.children( "option" ).each(function() {
          if ( $( this ).text().toLowerCase() === valueLowerCase ) {
            this.selected = valid = true;
            return false;
          }
        });
 
        // Found a match, nothing to do
        if ( valid ) {
          return;
        }
 
        // Remove invalid value
        this.input
          .val( "" )
          .attr( "title", value + " didn't match any item" )
          .tooltip( "open" );
        this.element.val( "" );
        this._delay(function() {
          this.input.tooltip( "close" ).attr( "title", "" );
        }, 2500 );
        this.input.autocomplete( "instance" ).term = "";
      },
 
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
    });
});