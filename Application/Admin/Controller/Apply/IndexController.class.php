<?php
namespace Admin\Controller\Apply;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

	}
	// 类型
	public function typeAction()
	{ 
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_type',
		);
		$this->sbset($set);
		
		$result = $this->ApplyService()->gettype();
		$this->assign('list', $result);
		$this->display();
	}
	// 添加修改类型
	public function type_editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->ApplyService()->edittype($post);
				$this->success('操作成功',U('type'));
			}
			else
			{ 
				unset($post['id']);
				if($this->ApplyService()->addtype($post))
				{ 
					$this->success('操作成功',U('type'));
				}
			}
		}
		else
		{
			$id = I('id');
			if($id)
			{ 
				$result = $this->ApplyService()->gettypebyid($id);
				$this->assign('info', $result);
			}
			$this->display();
		}
	}
	// 删除类型
	public function type_delAction()
	{ 
		$id = I('id');
		if($this->ApplyService()->deltype($id))
		{ 
			$this->success('操作成功',U('type'));
		}
	}
	// 品牌店铺内部申请列表
    public function firstAction()
	{
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_first',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'keyword' => $get['keyword'],
			'start' => $get['start'],
			'end' => $get['end'],
			'type' => 1,
			'apply_type' => $get['apply_type'],
    	);
		
    	
    	$result = $this->ApplyService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
		/*分类*/
		$this->assign('apply_type',$this->ApplyService()->gettype());
		
    	$this->display();
    }
	// 品牌向平台申请列表
    public function secondAction()
	{
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_second',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'keyword' => $get['keyword'],
			'start' => $get['start'],
			'end' => $get['end'],
			'type' => 2,
			'apply_type' => $get['apply_type'],
    	);
		
    	
    	$result = $this->ApplyService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
		/*分类*/
		$this->assign('apply_type',$this->ApplyService()->gettype());
		
    	$this->display();
    }
	// 代理商向平台申请列表
    public function threeAction()
	{
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_three',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'keyword' => $get['keyword'],
			'start' => $get['start'],
			'end' => $get['end'],
			'type' => 3,
			'apply_type' => $get['apply_type'],
    	);
		
    	
    	$result = $this->ApplyService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
		/*分类*/
		$this->assign('apply_type',$this->ApplyService()->gettype());
		
    	$this->display();
    }
	// 审批
	public function lists_editAction()
	{ 
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_'.I('ac'),
		);
		$this->sbset($set);
		
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['step'] == 1)
			{ 
				$post['examine_time_area'] = date('Y-m-d H:i:s');
			}
			if($post['step'] == 2)
			{ 
				$post['examine_time_platform'] = date('Y-m-d H:i:s');
			}
			unset($post['step']);
			$this->ApplyService()->editlists($post);
			$this->redirect('/apply/index/lists');
		}
		else
		{
			
			$get = I('get.');
			$id = $get['id'];
			$this->assign('get',$get);
			$result = $this->ApplyService()->getlistsbyid($id);
			$this->assign('info', $result);
			$this->display();
		}
	}
	// 详情
	public function lists_detailAction()
	{ 
		$set = array(
			'in'=>'apply',
			'ac'=>'apply_index_'.I('ac'),
		);
		$this->sbset($set);
		
		$id = I('id');
		$result = $this->ApplyService()->getlistsbyid($id);
		$this->assign('info', $result);
		$this->display();
	}
	private function ApplyService(){
		return D('Apply','Service');
	}
}