$(document).ready(function(){gebo_gal_grid.init();gebo_gal_grid.large();gebo_gal_grid.mixed();});gebo_gal_grid={init:function(){$('.wmk_grid').each(function(){$(this).find('.yt_vid,.self_vid,.vimeo_vid').append('<span class="vid_ico"/>');});},large:function(){$('#large_grid > ul').imagesLoaded(function(){var options={autoResize:true,container:$('#large_grid'),offset:6,itemWidth:220,flexibleItemWidth:false};var handler=$('#large_grid > ul > li');handler.wookmark(options);$('#large_grid > ul > li').on('mouseenter',function(){$(this).addClass('act_tools');}).on('mouseleave',function(){$(this).removeClass('act_tools');});$('#large_grid ul li > a').attr('rel','gallery').colorbox({maxWidth:'80%',maxHeight:'80%',opacity:'0.1',loop:true,slideshow:true,fixed:true});});},mixed:function(){$('#mixed_grid > ul').imagesLoaded(function(){var options={autoResize:true,container:$('#mixed_grid'),offset:6,itemWidth:220,flexibleItemWidth:false};var handler=$('#mixed_grid > ul > li');handler.wookmark(options);$('#mixed_grid > ul > li').on('mouseenter',function(){$(this).addClass('act_tools');}).on('mouseleave',function(){$(this).removeClass('act_tools');});$('#mixed_grid ul li > a').not('.int_video').attr('rel','mixed_gallery').colorbox({maxWidth:'80%',maxHeight:'80%',opacity:'0.3',photo:true,loop:false,fixed:true});if($(window).width()<768){var videoW='90%',videoH='90%';}else{var videoW='640px',videoH='360px';}
$('#mixed_grid .int_video').attr('rel','mixed_gallery').colorbox({width:videoW,height:videoH,opacity:'0.3',inline:true,loop:false,scrolling:false,fixed:true,onComplete:function(){$.colorbox.resize();}});});}};