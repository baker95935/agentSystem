<?php 

namespace app\admin\validate;

use think\Validate;


class Agentordersconsignee extends Validate
{
	protected $rule = [
		'consignee_name'  => 'require',
		'consignee_phone'  => 'require',
		'province'  => 'require',
		'city'  => 'require',
		'area'  => 'require',
		'address'  => 'require',
		'__token__' => 'token',
	];
	
	protected $message  =   [
		'consignee_name.require' => '收货人姓名必须填写',
		'consignee_phone.require' => '收货人电话必须填写',
		'province.require' => '省份必须填写',
		'city.require' => '城市必须填写',
		'area.require' => '地区必须填写',
		'address.require' => '收货人详细地址必须填写',
	];
	
}

?>