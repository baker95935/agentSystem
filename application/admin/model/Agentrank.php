<?php

namespace app\Admin\model;

use think\Model;

class Agentrank extends Model
{
    protected $table = 'agents_data_count_for_rank';

    /**
     * 关联代理商表
     */
    public function agents()
    {
    	return $this->hasOne('agents','agent_id','a_id');
    }

}