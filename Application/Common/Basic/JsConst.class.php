<?php
namespace Common\Basic;

class JsConst{
	static function jscall($action, $param, $callback = ''){
		if (cookie('no_jscall')) return ''; 
    	!is_array($param) && $param = array();
    	$dx = array(
    		'action'=>$action,
    		'param'=>$param,
    	);
    	$callback && $dx['param']['callback'] = $callback;
    	return 'jscall://'.json_encode($dx);
    }
    
    static function tbar($title, $callback){
    	return self::jscall('titlebar', ['title'=>$title], $callback);
    }
}