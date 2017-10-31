<?php
namespace Home\TagLib;
use Think\Template\TagLib;
class TagLibHy extends TagLib {
	protected $tags = array(
			'purview' => array('attr'=>'name', 'close'=>1),
	);
	
	public function _purview($tag,$content) {
		$name       =   $tag['name'];
		$parseStr   =   '<?php if(haspv('.$name.')): ?>'.$content.'<?php endif; ?>';
		return $parseStr;
	}
}