<?php
namespace Admin\Controller\Work;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'work',
			'ac'=>'work_index_target',
		);
		$this->sbset($set);
	}
	// 添加修改
	public function editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->WorkService()->edit($post);
				$this->success('操作成功',U('target'));
			}
			else
			{ 
				unset($post['id']);
				$post['time'] = date('Y-m-d H:i:s');
				if($this->WorkService()->add($post))
				{ 
					$this->success('操作成功',U('target'));
				}
			}
		}
		else
		{
			$id = I('id');
			if($id)
			{ 
				$result = $this->WorkService()->getworkbyid($id);
				$this->assign('info', $result);
			}
			$this->display();
		}
	}
	// 删除
	public function delAction()
	{ 
		$id = I('id');
		if($this->WorkService()->deltype($id))
		{ 
			$this->success('操作成功',U('type'));
		}
	}
	// 列表
    public function targetAction()
	{
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'keyword' => $get['keyword'],
			'start' => $get['start'],
			'end' => $get['end'],
    	);
		
    	
    	$result = $this->WorkService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
    	$this->display();
    }
	// 详情
	public function detailAction()
	{ 
		$id = I('id');
		$result = $this->WorkService()->getworkbyid($id);
		$this->assign('info', $result);
		$this->display();
	}
	private function WorkService(){
		return D('Work','Service');
	}
}