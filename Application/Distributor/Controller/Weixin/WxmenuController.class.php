<?php
namespace Distributor\Controller\Weixin;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\ApiConfig\Weixin as WeixinApiConfig;

class WxmenuController extends FController {

	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'weixin',
			'ac'=>'weixin_wxmenu_index',
		);
		$this->sbset($set);
		
		$map = array(
				'menu_pid'=>0,
				'distributor_id'=>$this->org_id
		);
		$menu = M('wx_menu')->where($map)->select();
		$this->assign('menu',$menu);
    }
	
    public function indexAction(){
    	$map = array(
    			'distributor_id'=>$this->org_id
    	);
		$menu = M('wx_menu')->where($map)->order('order_sort ASC')->select();

        //对微信菜单归类
        $menu=no_limit_cate($menu,'menu');
    	$this->assign('list',$menu);
		$this->display();
    }
	
    public function editAction($menu_id = 0){
		$map = array(
				'menu_id'=>$menu_id,
				'distributor_id'=>$this->org_id
		);
		$info = M('wx_menu')->where($map)->find();
		if (empty($info)) {
			$this->error('菜单不存在');
		}
		$this->assign('info',$info);
		
		if(IS_POST){
			$post = I('post.');
			$post['menu_status'] = $post['menu_status'] ? $post['menu_status'] : 0;
			$rules = array(
					array('menu_name', 'require', '菜单名称不能为空'),
					array('menu_value', 'require', '菜单值不能为空'),
					array('menu_type', 'require', '菜单类型不能为空'),
					array('menu_type',array(1,2,3),'菜单类型的值不正确',2,'in'),
			);
			if (!M('wx_menu')->validate($rules)->create($post)){
				$this->error(M('wx_menu')->getError());
			}
			$info = M('wx_menu')->save();
			
			if($info !== false){
				$this->success('修改成功', U('index'));
			}else{
				$this->error('修改失败');
			}
		}
		$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		$post['menu_status'] = $post['menu_status'] ? $post['menu_status'] : 0;
    		$post['add_time'] = time();
    		$post['distributor_id'] = $this->org_id;
    		$rules = array(
    				array('menu_name', 'require', '菜单名称不能为空'),
    				array('menu_value', 'require', '菜单值不能为空'),
    				array('menu_type', 'require', '菜单类型不能为空'),
    				array('menu_type',array(1,2,3),'菜单类型的值不正确',2,'in'),
    		);
    		if (!M('wx_menu')->validate($rules)->create($post)){
    			$this->error(M('wx_menu')->getError());
    		}
    		$info = M('wx_menu')->add();
    		if($info){
    			$this->success('添加成功', U('index'));
    		}else{
    			$this->error('添加失败');
    		}
    	}
    	$this->display('edit');
    }
	
    public function menuAction(){
    	$map = array(
    			'menu_status'=>1,
    			'distributor_id'=>$this->org_id
    	);
    	$list = M('wx_menu')->where($map)->order("order_sort ASC")->select();
    	if (empty($list)) {
    		$this->error('微信菜单不能为空');
    	}
		$btnArr = array();
		foreach((array)$list as $obj)
		{
			if(empty($obj))continue;
			if($obj['menu_pid'] == 0)
			{
				$sub_button = $this->get_menu_by_list($list,$obj['menu_id']);
				if(!empty($sub_button))
				{
					$btnArr[] = array(
							"type"=>"view",
							"name"=>urlencode($obj['menu_name']),
							'sub_button'=>$this->get_menu_by_list($list,$obj['menu_id'])
					);
				}
				else
				{
					if($obj['menu_type'] == 1)
					{
						$btnArr[] = array(
								"type"=>"click",
								"name"=>urlencode($obj['menu_name']),
								"key"=>$obj['menu_value']
						);
							
					}
					elseif ($obj['menu_type'] == 2)
					{
						$btnArr[] = array(
								"type"=>"view",
								"name"=>urlencode($obj['menu_name']),
								'url'=>$obj['menu_value']
						);
					}
					elseif ($obj['menu_type'] == 3)
					{
						$btnArr[] = array(
								"type"=>"location_select",
								"name"=>urlencode($obj['menu_name']),
								"key"=>$obj['menu_value']
						);
					}
				}
			}	
		}
		$btnJSON = $this->json_encode_no_zh(array("button"=>$btnArr)); 
		//print_r($btnJSON);
		//$config = WeixinApiConfig::jsapiConfig();
		$wx_configs = $this->configService()->findConfigs('weixin', $this->org_id);
		$access_token = getTokendb($this->org_id, $wx_configs);
		$result = $this->set_menu_list($btnJSON, $access_token); 
		//var_dump($result);exit;
		if($result['errcode']==0){
			$this->success('发布成功',U('index'));
		}else{
			$this->error($result['errmsg'],U('index'));
		}
    }
    
    //新建自定义链接
    public function set_menu_list($btnJSON,$access_token)
    {
    	$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
    	$result = json_decode(curlPost($url,urldecode($btnJSON)),true);
    	return $result;
    }
    
    function get_menu_by_list($array,$pid)
    {
    	$btnArr = array();
    	foreach($array as $row)
    	{
    		if(empty($row) || $row['menu_pid']!=$pid)continue;
    		if($row['menu_type']==1)
    		{
    			$btnArr[] = array(
    					"type"=>"click",
    					"name"=>urlencode($row['menu_name']),
    					"key"=>urlencode($row['menu_value'])
    			);
    		}
    		elseif ($row['menu_type']==2)
    		{
    			$btnArr[] = array(
    					"type"=>"view",
    					"name"=>urlencode($row['menu_name']),
    					"url"=>urlencode($row['menu_value'])
    			);
    		}
    		elseif ($row['menu_type']==3)
    		{
    			$btnArr[] = array(
    					"type"=>"location_select",
    					"name"=>urlencode($row['menu_name']),
    					"key"=>urlencode($row['menu_value'])
    			);
    		}
    	}
    	return $btnArr;
    }
    
    /**
     * 不转义中文字符和\/的 json 编码方法
     * @param array $arr 待编码数组
     * @return string
     */
    function json_encode_no_zh($arr) {
    	return urldecode(stripslashes(json_encode($arr)));
    }
 	
    public function delAction($menu_id = 0){
    	$map = array(
    			'menu_id'=>$menu_id,
    			'distributor_id'=>$this->org_id
    	);
    	$info = M('wx_menu')->where($map)->delete();
		if($info){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
    }
}