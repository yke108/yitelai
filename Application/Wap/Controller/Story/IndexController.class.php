<?php
namespace Wap\Controller\Story;
use Wap\Controller\WapController;
use Common\Basic\Pager;
use Common\Logic\PointLogic;
use Common\Basic\Genre;

class IndexController extends WapController {
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '粉丝故事会');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$pagesize = 8;
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$cat_id=I('cat_id')?I('cat_id'):I('get.cat_id');
		$this->assign('cat_id',$cat_id);
		$this->assign('json_cat_id',json_encode($cat_id));
		//var_dump(json_encode($cat_id));die();
    	$params = array(
    		'page'=>$p,
    		'pagesize'=>$pagesize,
    		'keyword'=>$get['keyword'],
    		'status'=>1,
			'cat_id'=>$cat_id,
			//'orderby'=>'sort_order desc,add_time desc',
			//'is_show'=>1,
    	);
    	$result = $this->storyinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $pagesize);
    		$this->assign('pages', $pager->show_arr);
    	}
		
		//分类
		$cat_params=array('pagesize'=>100);
		$cat_list=$this->storyinfoService()->catlist($cat_params);
		//$cat_list=node_merge($cat_result,0,1,'cat_id');
		$this->assign('fans_catlist',$cat_list);
		
		//print_r($cat_list);die();
		
		if(IS_AJAX){
			$html=$this->renderPartial('_index');
			$this->ajaxReturn(array('html'=>$html));
		}
		
		$this->display();
    }
	
	public function infoAction($id = 0){
		$map = array(
				'story_id'=>$id,
				'status'=>1,
				//'is_show'=>1,
		);
		$info = $this->storyinfoService()->findInfo($map);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		if (IS_AJAX && session('userid')) {
			$post = I('post.');
			$data = array(
					'story_id'=>$id,
					'user_id'=>session('userid'),
			);
			try {
				$res = $this->storyinfoService()->share($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('分享成功');
		}
		
		$story_user = $this->userService()->getUserInfo($info['user_id']);
		$this->assign('story_user', $story_user);
		
		//浏览数+1
		//storyAddViewNum($id);
		$params = array(
				'story_id'=>$info['story_id'],
				'user_id'=>$this->user['user_id'],
		);
		try {
			$this->storyinfoService()->addViewNum($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		//上一篇
		$map = array(
				'story_id'=>array('gt', $id),
				'status'=>1,
				'is_show'=>1,
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$pre = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('pre', $pre);
		
		//下一篇
		$map = array(
				'story_id'=>array('lt', $id),
				'status'=>1,
				'is_show'=>1,
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$next = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('next', $next);
		
		$this->display();
	}
	
	public function viewAction($id = 0){
		$map = array(
				'story_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		//浏览数+1
		storyAddViewNum($id);
		
		//上一篇
		$map = array(
				'story_id'=>array('gt', $id),
				'user_id'=>$this->user['user_id']
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$pre = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('pre', $pre);
		
		//下一篇
		$map = array(
				'story_id'=>array('lt', $id),
				'user_id'=>$this->user['user_id']
		);
		$orderBy = 'sort_order ASC, story_id DESC';
		$next = $this->storyinfoService()->searchInfo($map, $orderBy);
		$this->assign('next', $next);
		
		$this->display('Story/Index/info');
	}
	
	public function likeAction(){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', U('index/site/login'));
		}
		$post = I('post.');
		try {
			$post['type'] = 1;
			$post['user_id'] = $this->user['user_id'];
			$good_num = $this->storyinfoService()->infoLike($post);
			
			//点赞获得积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point_old'=>$this->user['pay_points'],
					'point'=>$point_config['likes']['fval'],
					'type'=>PointLogic::PointTypeLike,
					//'ref_user_id'=>$this->user['user_id'],
					'ref_id'=>$post['story_id']
			);
			try {
				$this->pointService()->addUserPoint($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//等级积分
			$params = array(
					'user_id'=>$this->user['user_id'],
					'rank_points'=>$point_config['likes']['fval']
			);
			$result = $this->userService()->setRank($params);
			if($result === false){
				$this->error('赠送等级积分失败');
			}
			
			$this->success('点赞成功', '', array('good_num'=>$good_num));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function clapAction(){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', U('index/site/login'));
		}
		$post = I('post.');
		try {
			$post['type'] = 2;
			$post['user_id'] = $this->user['user_id'];
			$bad_num = $this->storyinfoService()->infoClap($post);
			$this->success('拍砖成功', '', array('bad_num'=>$bad_num));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//打赏
	public function rewardAction($id = 0){
		$get = I('get.');
		$this->assign('get', $get);
	
		$map = array(
				'story_id'=>$id,
				'status'=>1,
				//'is_show'=>1,
		);
		$story_info = $this->storyinfoService()->findInfo($map);
		if(empty($story_info)) $this->error('内容不存在');
		$story_user = $this->userService()->getUserInfo($story_info['user_id']);
		if(empty($story_user)) $this->error('作者不存在');
	
		if (IS_POST){
			$post = I('post.');
				
			if (empty($this->user)) {
				$this->error('请先登录', U('index/site/login'));
			}
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
			if ($post['reward_amount'] <= 0) {
				$this->error('请输入打赏金额');
			}
				
			$post['user_id'] = $this->user['user_id'];
			$post['story_id'] = $story_info['story_id'];
			$post['story_user_id'] = $story_user['user_id'];
			try {
				$reward_id = $this->storyinfoService()->createReward($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$post['reward_id'] = $reward_id;
			if($post['pay_id'] == 1) { //余额支付
				try {
					$result = $this->storyinfoService()->payReward($post);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
				$this->success('打赏成功', U('success', array('id'=>$reward_id)));
			} elseif ($post['pay_id'] == 2) { //微信支付
				$wxconf = $this->configService()->findWeixinConfigs();
				$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
				//$unifiedOrder->setParameter("openid",$this->user['openid']);//openid
				$unifiedOrder->setParameter("openid",cookie('openid_ck8'));
				$unifiedOrder->setParameter("body","打赏文章");//商品描述
				$unifiedOrder->setParameter("out_trade_no",$reward_id);//订单号
				$unifiedOrder->setParameter("total_fee",($post['reward_amount']*100));//总金额
				$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/weixin/reward.php');//通知地址
				$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
				//var_dump($unifiedOrder);
				$jsApiParameters = $unifiedOrder->createTradeDataForJS();
				//var_dump($jsApiParameters);exit;
				$this->assign("reward_id",$reward_id);
				$this->assign("jsApiParameters",$jsApiParameters);
				$this->display('weixinpay');
			} elseif ($post['pay_id'] == 3) { //支付宝支付
				
			}
		}
	
		$this->assign('info', $story_info);
		$this->assign('story_user', $story_user);
	
		$this->display();
	}
	
	public function successAction($id = 0) {
		$params = array(
				'reward_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->storyinfoService()->rewardInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
	
		$this->display();
	}
	
    private function lotteryService(){
    	return D('Lottery', 'Service');
    }
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function inquiryService(){
		return D('Inquiry', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
	
	private function goodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage', 'Service');
	}
}