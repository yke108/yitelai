$(document).ready(function(){function openKCFinder(field_name,url,type,win){alert("Field_Name: "+ field_name+"nURL: "+ url+"nType: "+ type+"nWin: "+ win);tinyMCE.activeEditor.windowManager.open({file:'/file-manager/browse.php?opener=tinymce&type='+ type,title:'KCFinder',width:700,height:500,resizable:"yes",inline:true,close_previous:"no",popup_css:false},{window:win,input:field_name});return false;}
$('textarea#wysiwg_full').tinymce({script_url:'lib/tinymce/tinymce.min.js',theme:"modern",plugins:"autoresize,table,image,link,emoticons,preview,media,contextmenu,paste,fullscreen,noneditable,template,advlist",file_browser_callback:function openKCFinder(field_name,url,type,win){tinyMCE.activeEditor.windowManager.open({file:'file-manager/browse.php?opener=tinymce&type='+ type+'&dir=image/themeforest_assets',title:'KCFinder',width:700,height:500,resizable:"yes",inline:true,close_previous:false},{window:win,input:field_name});return false;}});});