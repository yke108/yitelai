<?php
namespace News\Controller;
use Think\Controller;

class EmptyController extends Controller {	
    public function indexAction(){
    	$this->redirect('index/index');
	}
	
    protected function _empty(){
        $this->redirect('index/index');
    }
}