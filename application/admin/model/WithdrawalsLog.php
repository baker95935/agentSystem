<?php

namespace app\admin\model;

use think\Model;

class WithdrawalsLog extends Model
{
    protected $table = 'withdrawals_log';

    // 审核状态
    public $audit = array(
		1=>'待审核',
		2=>'已审核',
		3=>'驳回',
	);
    
    //提现历史的审核状态
    public $auditHistory=array(
    	2=>'已审核',
    	3=>'驳回',
    );

    // 账户类型
    public $type = [
        1 => '支付宝账号',
        2 => '银行卡账号',
        3 => '微信支付帐号'
    ];

    // 账户类型
    public function getTypeAttr($value)
    {
        $type = $this->type;
        return $type[$value];
    }

    // 审核状态
    public function getAuditAttr($value)
    {
        $audit = $this->audit;
        return $audit[$value];
    }

    // 定义和代理商表的关联
    public function agents()
    {
        return $this->hasOne('Agents','agent_id','a_id');
    }
}
