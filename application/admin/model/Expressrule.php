<?php

namespace app\admin\model;

use think\Model;

class Expressrule extends Model
{
    protected $table = 'express_rule';
    protected $field = '`id`,`name`,`type`,`first_num`,`first_price`,`continue_num`,`continue_price`,`free_num`,`is_inside`,`cost`,`province`,`city`,`area`,`create_ctime`,`is_valid`,`is_del`';

    // 获取列表数据(未删除状态)
    public function RuleList($param)
    {
    	$list = $this->field($this->field)->where(['is_del'=>0])->order('id')->paginate(config('paginate.list_rows'),false,['query'=>$param]);
    	return $list;
    }

    // 列表数据中的省级名称替换
    public function RuleListProvinceSwitch($param)
    {
    	$list = $this->RuleList($param);
    	if($list)
    	{
    		foreach ($list as $key => $value)
    		{
    			if(!empty($value['province']))
    			{
    				$names = model('BasicDataAddress')->field('name')->where(['id'=>['in',$value['province']]])->select();
    				$names = array_map('array_values', (collection($names)->toArray()));
    				$list[$key]['provinces'] = array_reduce($names,'array_reduction_for_city');// 获取省名称字符串
    			}else{
    				$list[$key]['provinces'] = '';
    			}
    		}
    	}
    	return $list;
    }

    // 删除修改
    public function delUpdate($id)
    {
    	$result = $this->where(['id'=>$id])->update(['is_del'=>1]);
    	return $result;
    }

    // 获取所有未删除的运费规则
    public function AllUndelRuleList($is_del = 0)
    {
    	$list = $this->field($this->field)->where(['is_del'=>$is_del])->order('id')->select();
    	return $list;
    }

    /**
     * 检查名称是否重复
     * @param  string  $name 规则名称
     * @param  integer $id   规则ID
     * @return Boolean        是否重复:true重复false不重复
     */
    public function checkNameIsRepeat($name,$id=0)
    {
        $return = false;
        if (!empty($name))
        {
            $result = $this->where(['name'=>$name,'is_del'=>0,'id'=>['neq',$id]])->count();
            if($result > 0)
            {
                $return = true;// 存在重复名称
            }
        }
        return $return;
    }
}