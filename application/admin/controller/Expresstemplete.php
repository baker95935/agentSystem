<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Expresstemplete extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $received    = request();
        $search_name = $received->param('name','');
        $list        = model('Expresstemplete')->TempleteList($received->param());
        $this->assign('dictionaryList',$list);
        $this->assign('name',$search_name);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $rule_list = model('Expressrule')->AllUndelRuleList();
        $this->assign('list',$rule_list);
        return $this->fetch('editor');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save()
    {
        $received = request();
        $data['name']             = $received->param('name');
        $data['express_rule_ids'] = $received->param('rules/a');
        $data['express_rule_ids'] = empty($data['express_rule_ids']) ? '' : implode(',', $data['express_rule_ids']);
        $data['create_ctime'] = date('Y-m-d H:i:s');
        $name_repeat = model('Expresstemplete')->checkNameIsRepeat($data['name']);
        if($name_repeat)
        {
            return ['error'=>['msg'=>'名称已存在，请重新修改']];
        }
        $result = model('Expresstemplete')->insert($data);
        if($result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    // '启用'修改
    public function ajaxEnable()
    {
        $received = request();
        $id = $received->param('eid');
        $data['is_valid'] = $received->param('sta');
        if ($id)
        {
            $result = model('Expresstemplete')->where(['id'=>$id,'is_del'=>0])->update($data);
            if (false !== $result)
            {
                return ['msg'=>'操作成功'];
            } else {
                return ['error'=>['msg'=>'操作失败']];
            }
        } else {
            return ['error'=>['msg'=>'参数错误']];
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if($id)
        {
            $data = model('Expresstemplete')->where(['is_del'=>0,'id'=>$id])->find();
            if(!$data)
            {
                $this->redirect('index');
            }
            $this->assign('info',$data);
            $rule_list = model('Expressrule')->AllUndelRuleList();
            $this->assign('list',$rule_list);
            return $this->fetch('modify');
        }else{
            $this->redirect('index');
        }
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if(empty($id))
        {
            return ['error'=>['msg'=>'参数错误']];
        }
        $data['name']             = $request->param('name');
        $data['express_rule_ids'] = $request->param('rules/a');
        $data['express_rule_ids'] = empty($data['express_rule_ids']) ? '' : implode(',', $data['express_rule_ids']);
        $name_repeat = model('Expresstemplete')->checkNameIsRepeat($data['name'],$id);
        if($name_repeat)
        {
            return ['error'=>['msg'=>'名称已存在，请重新修改']];
        }
        $result = model('Expresstemplete')->where(['id'=>$id])->update($data);
        if(false !== $result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete()
    {
        $received = request();
        $del_id   = $received->param('did');
        if ($del_id)
        {
            $data['is_del'] = 1;
            $result = model('Expresstemplete')->where(['id'=>['in',$del_id]])->update($data);
            if (false !== $result)
            {
                return ['msg'=>'操作成功'];
            } else {
                return ['error'=>['msg'=>'操作失败']];
            }
        } else {
            return ['error'=>['msg'=>'参数错误']];
        }
    }

}