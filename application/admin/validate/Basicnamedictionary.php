<?php 

namespace app\admin\validate;

use think\Validate;


class Basicnamedictionary extends Validate
{
	protected $rule = [
		'name'  => 'require|chs|length:1,20',
		'value'  => 'require|alphaDash|length:1,60|unique:basic_name_dictionary',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'name.require' => '名称必须填写',
		'name.chs'     => '名称必须是汉字',
		'name.length'   => '名称必须在1和10个之间',
		
		'value.require' => '值必须填写',
		'value.unique' => '值已存在请重新填写',
		'value.alphaDash'     => '值只能是字母和下划线',
		'value.length'   => '奖励比例必须在1和60个之间',
	];
	
}

?>