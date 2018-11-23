<?php

namespace app\admin\validate;

use think\Validate;

class Productmanagement extends Validate
{

    protected $rule = [
        'product_name'  =>  'require|unique:product_management',
        'category_id'       =>  'require',
     ];

    protected $message  =   [
        'product_name.require' => '名称必须填写',
        'product_name.unique'=>'该商品已添加',
        'category_id.require' => '类目必须填写',
      ];
    protected $scene = [
        'saveEdit' => ['product_name.unique'=>'require|unique:product_management,product_name'],
    ];

}

?>