<?php
namespace Information\Controller;
use Think\Controller;

class EmptyController extends Controller {
	function indexAction(){
        $this->redirect('site/error/index');
	}
}
    

