<?php

namespace app\admin\validate;

use think\Validate;

class Productclassify extends Validate
{

    protected $rule = [
        'classify_name'  =>  'require|max:30',
    ];

    protected $message  =   [
        'classify_name.require' => '名称必须填写',
    ];

}

?>