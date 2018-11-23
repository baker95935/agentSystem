<?php

namespace app\admin\validate;

use think\Validate;

class Productlabel extends Validate
{
    protected $rule = [
        'product_name'  =>  'require|max:20',
    ];

}

?>