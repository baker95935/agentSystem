<?php

namespace app\admin\model;

use think\Model;

class Expresstemplete extends Model
{
    protected $table = 'freight_templete';
    protected $field = '`id`,`name`,`express_rule_ids`,`is_valid`,`create_ctime`,`is_del`';

    // 获取模板列表数据(带名称搜索)
    public function TempleteList($param)
    {
    	if(isset($param['name']))
    	{
    		$this->where('name','like',"%{$param['name']}%");
    	}
    	$list = $this->field($this->field)->where(['is_del'=>0])->order('id')->paginate(config('paginate.list_rows'),false,['query'=>$param]);
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

    /**
     * 获取全部模板列表
     */
    public function getAllList($arr = ['is_del'=>0,'is_valid'=>1])
    {
        $list = $this->field($this->field)->where($arr)->select();
        return $list;
    }
}