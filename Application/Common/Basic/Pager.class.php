<?php
namespace Common\Basic;

class Pager{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 10;// 分页栏每页显示的页数
	public $lastSuffix = false; // 最后一页是否显示总页数

    private $p       = 'p'; //分页参数名
    private $url     = ''; //当前链接URL
    private $nowPage = 1;

	// 分页显示定制
    private $config  = array(
        'header' => '<span class="rows" style="font-size:14px">共 %TOTAL_ROW% 条记录</span> | ',
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '首页',
        'last'   => '末页',
        'theme'  => '%HEADER%%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%',
    );

    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array(),$add_id) {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
		$this->add_id=$add_id;
		if($add_id!=''){
			$this->is_layout_class='cs_show_modal';
		}
    }

    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows) return '';

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="prev cs_ajax_link '.$this->is_layout_class.'" cs_id="'.$this->add_id.'" target="_self" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a target="_self" cs_id="'.$this->add_id.'" class="next cs_ajax_link '.$this->is_layout_class.'" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';

        //第一页
        $the_first = '';
        if($this->totalRows > $this->listRows ){
            $the_first = '<a class="first cs_ajax_link '.$this->is_layout_class.'" cs_id="'.$this->add_id.'" target="_self" href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
        }

        //最后一页
        $the_end = '';
        if($this->totalRows > $this->listRows ){
            $the_end = '<a class="end cs_ajax_link '.$this->is_layout_class.'" cs_id="'.$this->add_id.'" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
        }

        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
			if(($this->nowPage - $now_cool_page) <= 0 ){
				$page = $i;
			}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
				$page = $this->totalPages - $this->rollPage + $i;
			}else{
				$page = $this->nowPage - $now_cool_page_ceil + $i;
			}
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<a class="num cs_ajax_link '.$this->is_layout_class.'" cs_id="'.$this->add_id.'" href="' . $this->url($page) . '">' . $page . '</a>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<span class="current">' . $page . '</span>';
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']);
        return "<div class='pager'>{$page_str}</div>";
    }
    
    public function show_pc() {
    	if(0 == $this->totalRows) return '';
    
    	/* 生成URL */
    	$this->parameter[$this->p] = '[PAGE]';
    	$this->url = U(ACTION_NAME, $this->parameter);
    	/* 计算分页信息 */
    	$this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
    	if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
    		$this->nowPage = $this->totalPages;
    	}
    
    	/* 计算分页零时变量 */
    	$now_cool_page      = $this->rollPage/2;
    	$now_cool_page_ceil = ceil($now_cool_page);
    	$this->lastSuffix && $this->config['last'] = $this->totalPages;
    
    	//上一页
    	$up_row  = $this->nowPage - 1;
    	$up_page = $up_row > 0 ? '<a class="turn_btn" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '<a href="javascript:;">'.$this->config['prev'].'</a>';
    
    	//下一页
    	$down_row  = $this->nowPage + 1;
    	$down_page = ($down_row <= $this->totalPages) ? '<a class="turn_btn" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '<a href="javascript:;">'.$this->config['next'].'</a>';
    
    	//第一页
    	$the_first = '';
    	if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
    		$the_first = '<a href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
    	}
    
    	//最后一页
    	$the_end = '';
    	if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
    		$the_end = '<a href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
    	}
    
    	//数字连接
    	$link_page = "";
    	for($i = 1; $i <= $this->rollPage; $i++){
    		if(($this->nowPage - $now_cool_page) <= 0 ){
    			$page = $i;
    		}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
    			$page = $this->totalPages - $this->rollPage + $i;
    		}else{
    			$page = $this->nowPage - $now_cool_page_ceil + $i;
    		}
    		if($page > 0 && $page != $this->nowPage){
    
    			if($page <= $this->totalPages){
    				$link_page .= '<a href="' . $this->url($page) . '">' . $page . '</a>';
    			}else{
    				break;
    			}
    		}else{
    			if($page > 0 && $this->totalPages != 1){
    				$link_page .= '<a href="javascript:;" class="on">' . $page . '</a>';
    			}
    		}
    	}
    
    	//替换分页内容
    	$page_str = str_replace(
    			array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
    			array('', $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
    			$this->config['theme']);
    	return $page_str;
    }
    
    public function show_arr(){
    
    	if(0 == $this->totalRows) return '';

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="cs_ajax_link" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '<a href="javascript:void(0);">' . $this->config['prev'] . '</a>';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a class="cs_ajax_link" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '<a href="javascript:void(0);">' . $this->config['next'] . '</a>';
    	
    
    	//数字连接
    	$link_page = "";    	
    	for($m=1;$m<=$this->totalPages;$m++){
    		if($m){
    			if($m==$this->nowPage){
    			
    				$link_page .= '<a class="current" href="javascript:void(0);">'.$m.'</a>';
    			}
    			elseif($m==1 || $m==$this->totalPages || $m>=$this->nowPage-2 && $m<=$this->nowPage+2){
    				$link_page .= '<a class="cs_ajax_link" href="' . $this->url($m) . '">' . $m . '</a>';
    			}elseif($m==$this->nowPage-3){
    				$link_page .= '<a href="javascript:void(0);">...</a>';
    			}elseif($m==$this->nowPage+4){
    				$link_page .= '<a href="javascript:void(0);">...</a>';
    			}
    		}
    		 
    	}
    	
    
    
    	//替换分页内容
    	$page_str = str_replace(
    			array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
    			array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
    			$this->config['theme']);
    	return "{$page_str}";
    }
    
    public function show_turn() {
    	if(0 == $this->totalRows) return '';
    
    	/* 生成URL */
    	$this->parameter[$this->p] = '[PAGE]';
    	$this->url = U(ACTION_NAME, $this->parameter);
    	/* 计算分页信息 */
    	$this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
    	if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
    		$this->nowPage = $this->totalPages;
    	}
    
    	/* 计算分页零时变量 */
    	$now_cool_page      = $this->rollPage/2;
    	$now_cool_page_ceil = ceil($now_cool_page);
    	$this->lastSuffix && $this->config['last'] = $this->totalPages;
    
    	//上一页
    	$up_row  = $this->nowPage - 1;
    	$up_page = $up_row > 0 ? '<a class="turn_btn" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '<a href="javascript:;">'.$this->config['prev'].'</a>';
    
    	//下一页
    	$down_row  = $this->nowPage + 1;
    	$down_page = ($down_row <= $this->totalPages) ? '<a class="turn_btn" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '<a href="javascript:;">'.$this->config['next'].'</a>';
    
    	//第一页
    	$the_first = '';
    	if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
    		$the_first = '<a href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
    	}
    
    	//最后一页
    	$the_end = '';
    	if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
    		$the_end = '<a href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
    	}
    
    	//数字连接
    	$link_page = "";
    	for($i = 1; $i <= $this->rollPage; $i++){
    		if(($this->nowPage - $now_cool_page) <= 0 ){
    			$page = $i;
    		}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
    			$page = $this->totalPages - $this->rollPage + $i;
    		}else{
    			$page = $this->nowPage - $now_cool_page_ceil + $i;
    		}
    		if($page > 0 && $page != $this->nowPage){
    
    			if($page <= $this->totalPages){
    				$link_page .= '<a href="' . $this->url($page) . '">' . $page . '</a>';
    			}else{
    				break;
    			}
    		}else{
    			if($page > 0 && $this->totalPages != 1){
    				$link_page .= '<a href="javascript:;" class="on">' . $page . '</a>';
    			}
    		}
    	}
    
    	//替换分页内容
    	$page_str = str_replace(
    			array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
    			array('', '', $up_page, $down_page, '', '', '', '', ''),
    			$this->config['theme']);
    	return $page_str;
    }
	
	public function show_array(){
    
    	if(0 == $this->totalRows) return array();

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = U(ACTION_NAME, $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;
    	$prev_row  = $this->nowPage - 1>0?$this->nowPage - 1:1;
		$next_row  = $this->nowPage +1;
		
		for($i = 1; $i <= $this->totalPages; $i++){
    		$all[$i]=$i;
    	}
		
    	$page_array=array(
						'list_rows'=>$this->listRows,
						'total_line'=>$this->totalRows,
						'total_pages'=>$this->totalPages,
						'current'=>$this->nowPage,
						'first'=>$this->config['first'],
						'last'=>$this->totalPages,
						'prev'=>$this->config['prev'],
						'prev_link'=>$prev_row > 0 ?  $this->url($prev_row) : "javascript:void(0)",
						'next'=>$this->config['next'],
						'next_link'=>($next_row <= $this->totalPages) ? $this->url($next_row): "javascript:void(0);",
						'all'=>$all,
						'parameter'=>$this->parameter,
						);
		
    	return $page_array;
    }
	
}
