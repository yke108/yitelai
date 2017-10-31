<?php
namespace Admin\Controller\Personnel;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
	}
	// 组别
	public function groupAction()
	{ 
		$set = array(
			'in'=>'personnel',
			'ac'=>'personnel_index_group',
		);
		$this->sbset($set);
		$result = $this->PersonnelService()->getgroup();
		$this->assign('list', $result);
		$this->display();
	}
	// 添加修改组别
	public function group_editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->PersonnelService()->editgroup($post);
				$this->success('操作成功',U('group'));
			}
			else
			{ 
				unset($post['id']);
				if($this->PersonnelService()->addgroup($post))
				{ 
					$this->success('操作成功',U('group'));
				}
			}
		}
		else
		{
			$id = I('id');
			if($id)
			{ 
				$result = $this->PersonnelService()->getgroupbyid($id);
				$this->assign('info', $result);
			}
			$this->display();
		}
	}
	// 删除组别
	public function group_delAction()
	{ 
		$id = I('id');
		if($this->PersonnelService()->delgroup($id))
		{ 
			$this->success('操作成功',U('group'));
		}
	}
	// 岗位
	public function departmentAction()
	{ 
		$set = array(
			'in'=>'personnel',
			'ac'=>'personnel_index_department',
		);
		$this->sbset($set);
		$result = $this->PersonnelService()->getdepartment();
		$this->assign('list', $result);
		$this->display();
	}
	// 添加修改岗位
	public function department_editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->PersonnelService()->editdepartment($post);
				$this->success('操作成功',U('department'));
			}
			else
			{ 
				unset($post['id']);
				if($this->PersonnelService()->adddepartment($post))
				{ 
					$this->success('操作成功',U('department'));
				}
			}
		}
		else
		{
			$id = I('id');
			if($id)
			{ 
				$result = $this->PersonnelService()->getdepartmentbyid($id);
				$this->assign('info', $result);
			}
			/*组别*/
			$this->assign('group', $this->PersonnelService()->getgroup());
			$this->display();
		}
	}
	// 删除岗位
	public function department_delAction()
	{ 
		$id = I('id');
		if($this->PersonnelService()->deldepartment($id))
		{ 
			$this->success('操作成功',U('department'));
		}
	}
	// 列表
    public function listsAction()
	{
		$set = array(
			'in'=>'personnel',
			'ac'=>'personnel_index_lists',
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
			'group_id' => $get['group_id'],
			'department_id' => $get['department_id'],
    	);
		
    	
    	$result = $this->PersonnelService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
		/*组别*/
		$this->assign('group', $this->PersonnelService()->getgroup());
		
    	$this->display();
    }
	// 添加修改列表
	public function lists_editAction()
	{ 
		if(IS_POST)
		{ 
			$post = I('post.');
			if($post['id'])
			{ 
				$this->PersonnelService()->editlists($post);
				$this->success('操作成功',U('lists'));
			}
			else
			{ 
				unset($post['id']);
				$post['number'] = date('YmdHis');
				$post['time'] = date('Y-m-d H:i:s');
				if($this->PersonnelService()->addlists($post))
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
				$result = $this->PersonnelService()->getlistsbyid($id);
				$this->assign('info', $result);
			}
			/*组别*/
			$this->assign('group', $this->PersonnelService()->getgroup());
			$this->display();
		}
	}
	// 删除
	public function lists_delAction()
	{ 
		$id = I('id');
		if($this->PersonnelService()->dellists($id))
		{ 
			$this->success('操作成功',U('lists'));
		}
	}
	// 根据组别获取岗位
	public function get_departmentAction()
	{ 
		if(IS_POST)
		{ 
			$goup_id = I('group_id');
			$result = $this->PersonnelService()->getdepartmentbygroud($goup_id);
			if($result)
			{ 
				foreach($result as $v)
				{ 
					$selected = '';
					if($v['id'] == I('department'))
					{ 
						$selected = 'selected';
					}
					$option.='<option value="'.$v['id'].'" '.$selected.'>'.$v['department'].'</option>';
				}
				echo $option;
				return;
			}
		}
	}
	private function PersonnelService(){
		return D('Personnel','Service');
	}
}