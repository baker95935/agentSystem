<?php 

namespace app\admin\validate;

use think\Validate;

class Adminmenu extends Validate
{
	protected $rule = [
		'name'  =>  'require|max:30',
		'group' =>  'require|max:30',
		'module' => 'require|max:30',
		'action' => 'require|max:30',
		'order' => 'require|number',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'name.require' => '名称必须填写',
	];
}

?>