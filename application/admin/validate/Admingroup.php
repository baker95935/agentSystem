<?php 

namespace app\admin\validate;

use think\Validate;

class Admingroup extends Validate
{
	protected $rule = [
		'name'  =>  'require|max:30',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'name.require' => '名称必须填写',
	];
	
}

?>