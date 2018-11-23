<?php

namespace app\index\model;

use think\Model;

class FinancialAccount extends Model
{
    protected $table = 'agent_financial_account';

    /**
     * 获取代理商资金账户(支付宝/银行卡)的设置状态
     *
     * @param $a_id int 代理商ID
     * @return array []
     */
    public function getAgentFinancialState($a_id)
    {
        $statue = ['ali_set'=>0,'bank_set'=>0];
    	$list = $this->where(['a_id'=>$a_id,'is_del'=>0,'type'=>['in','1,2']])->select();
    	if($list)
    	{
            foreach ($list as $key => $value)
            {
                if($value['type'] == 1 && !empty($value['account']))
                {
                    $statue['ali_set'] = 1;
                }
                if($value['type'] == 2 && !empty($value['account']) && !empty($value['bank']) && !empty($value['name']))
                {
                    $statue['bank_set'] = 1;
                }
            }
    	}
		return $statue;
    }

    /**
     * 返回当前代理商的资金账户(优先顺序:3微信/2银行卡/1支付宝)
     *
     * @param $a_id int 代理商ID
     * @param $type int 账户类型
     * @return Boolean/Array
     */
    public function getAgentFinancialAccountArray($a_id,$type=0)
    {
        $where = ['a_id'=>$a_id,'is_del'=>0,'type'=>['in','1,2,3']];
        if($type != 0)
        {
            $where['type'] = $type;
        }
        $all = $this->where($where)->select();
        if($all)
        {
            foreach ($all as $key => $val)
            {
                if($val['type'] == 3)
                {
                    $return = [
                        'name'       => '微信支付',
                        'type'       => $val['type'],
                        'account'    => $val['account'],
                        'is_default' => $val['is_default']
                    ];
                    break;
                }else{
                    if($val['type'] == 2)
                    {
                        $return = [
                            'name'       => '银行卡',
                            'type'       => $val['type'],
                            'account'    => $val['account'],
                            'is_default' => $val['is_default']
                        ];
                        break;
                    }else{
                        $return = [
                            'name'       => '支付宝',
                            'type'       => $val['type'],
                            'account'    => $val['account'],
                            'is_default' => $val['is_default']
                        ];
                    }
                }
            }
            return $return;
        }else{
            return false;
        }
    }
}