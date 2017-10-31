<?php
namespace Gallery\Controller;
use Think\Controller;

class EmptyController extends Controller {
	function indexAction(){
        $this->redirect('site/error/index');
	}
}
    

