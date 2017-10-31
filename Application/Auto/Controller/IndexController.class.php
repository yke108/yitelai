<?php
namespace Auto\Controller;
use Think\Controller;

class IndexController extends Controller {

	
    public function indexAction($id = 0){
		header("Content-type: text/html; charset=utf-8");
		try{
			$result=$this->rechargeService()->revalueMoney();
		}catch(\Exception $e){
			echo $e->getMessage();
		}
		echo $result;
		die();
    }
	public function run_recharge(){
		
	}
	private function rechargeService(){
		return D('Recharge','Service');
	}
	
}