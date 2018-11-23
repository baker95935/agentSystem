<?php

namespace app\admin\validate;

use think\Validate;


class Promotiongiftinfo extends Validate
{
	protected $rule = [
		'name'  => 'require',
		'pic'  => 'require',
		'price'  => 'require|float',
		'number'  => 'require|integer|between:1,100',
		'product_id' => 'require',
		'__token__' => 'token',
	];

	protected $message  =   [
		'name.require' => '礼包名称必须填写',
		'pic.require' => '封面图片必须上传',

		'price.require' => '礼包价格必须填写',
		'price.float' => '礼包价格必须是数字',

		'number.require' => '礼包数量必须填写',
		'number.integer'     => '礼包数量必须是数字',
		'number.between'   => '礼包数量必须在1和100之间',
		'product_id.require' => '请选择关联商品'
	];

}

?>