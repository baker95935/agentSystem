<?php

namespace app\admin\validate;

use think\Validate;

class Productgrouping extends Validate
{
    protected $rule = [
        'grouping_name'  =>  'require|max:20',
    ];

}

?>