<?php
namespace Admin\Controller\Fine;
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
			'in'=>'fine',
			'ac'=>'fine_index_type',
		);
		$this->sbset($set);
		
		$result = $this->FineService()->gettype();
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
				$this->FineService()->edittype($post);
				$this->success('操作成功',U('type'));
			}
			else
			{ 
				unset($post['id']);
				if($this->FineService()->addtype($post))
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
				$result = $this->FineService()->gettypebyid($id);
				$this->assign('info', $result);
			}
			$this->display();
		}
	}
	// 删除类型
	public function type_delAction()
	{ 
		$id = I('id');
		if($this->FineService()->deltype($id))
		{ 
			$this->success('操作成功',U('type'));
		}
	}
	// 罚款列表
    public function listsAction()
	{
		$set = array(
			'in'=>'fine',
			'ac'=>'fine_index_lists',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'fine_type' => $get['fine_type'],
			'start' => $get['start'],
			'end' => $get['end'],
    	);
		
    	$result = $this->FineService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
		/*分类*/
		$this->assign('fine_type',$this->FineService()->gettype());
		
    	$this->display();
    }
	// 添加修改罚款
	public function lists_editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->FineService()->editlists($post);
				$this->success('操作成功',U('lists'));
			}
			else
			{ 
				unset($post['id']);
				$post['number'] = date('YmdHis');
				$post['time'] = date('Y-m-d H:i:s');
				if($this->FineService()->addlists($post))
				{ 
					$this->success('操作成功',U('lists'));
				}
			}
		}
		else
		{
			$id = I('id');
			if($id)
			{ 
				$result = $this->FineService()->gettypebyid($id);
				$this->assign('info', $result);
			}
			/*分类*/
			$this->assign('fine_type',$this->FineService()->gettype());
			$this->display();
		}
	}
	// 审核
	public function lists_checkAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['step'] == 1)
			{
				$post['time_first'] = date('Y-m-d H:i:s');
			}
			if($post['step'] == 2)
			{ 
				$post['time_second'] = date('Y-m-d H:i:s');
			}
			unset($post['step']);
			if($this->FineService()->editlists($post))
			{ 
				$this->success('操作成功',U('lists'));
			}
		}
		else
		{
			$set = array(
				'in'=>'fine',
				'ac'=>'fine_index_lists',
			);
			$this->sbset($set);
		
			$get = I('get.');
			$id = $get['id'];
			$this->assign('get',$get);
			$result = $this->FineService()->getlistsbyid($id);
			$this->assign('info', $result);
			$this->display();
		}
	}
	// 详情
	public function lists_detailAction()
	{ 
		$set = array(
			'in'=>'fine',
			'ac'=>'fine_index_lists',
		);
		$this->sbset($set);
			
		$id = I('id');
		$result = $this->FineService()->getlistsbyid($id);
		$this->assign('info', $result);
		/*分类*/
		$this->assign('fine_type',$this->FineService()->gettype());
		$this->display();
	}
	private function FineService(){
		return D('Fine','Service');
	}
}