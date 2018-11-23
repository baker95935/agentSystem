<?php

namespace app\admin\model;

use think\Model;

class ProfitToStock extends Model
{
    protected $table = 'profit_to_stock_log';

    // 与代理商表关系
    public function agents()
    {
    	return $this->hasOne('Agents','agent_id','a_id');
    }
}