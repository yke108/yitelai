<?php
namespace Admin\Controller\Site;
use Think\Controller;

class ErrorController extends Controller {
	function indexAction(){
		layout(false);
        $this->display();
	}
}
    

