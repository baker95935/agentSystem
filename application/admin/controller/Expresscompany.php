<?php

namespace app\Admin\controller;

use think\Controller;
use think\Request;

class Expresscompany extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $received = request();
        $search = $received->param('name');
        $list = model('Expresscompany')->getAllExpressCompany($search);
        $this->assign('search',$search);
        $this->assign('list',$list);
        return $this->fetch('index');
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $name = $request->param('name','','trim');
        if(!empty($name))
        {
            $isset = model('Expresscompany')->checkNameIsSet($name);
            if($isset)
            {
                return ['error'=>['msg'=>'该公司已存在']];
            }else{
                $data['name'] = $name;
                $data['create_ctime'] = date('Y-m-d H:i:s');
                $define = model('Agentorderdelivery')->expressName;
                $code   = array_search($name,$define);
                $data['code'] = $code ? $code : '';
                $result = model('Expresscompany')->insert($data);
                if($result)
                {
                    return ['msg'=>'操作成功'];
                }else{
                    return ['error'=>['msg'=>'操作失败']];
                }
            }
        }else{
            return ['error'=>['msg'=>'名称不能为空']];
        }
    }

    /**
     * 保存更新
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if($id)
        {
            $name = $request->param('name','','trim');
            $isset = model('Expresscompany')->where(['id'=>['neq',$id],'name'=>$name,'is_del'=>0])->count();
            if($isset)
            {
                return ['error'=>['msg'=>'该公司已存在']];
            }else{
                $define = model('Agentorderdelivery')->expressName;
                $code   = array_search($name,$define);
                $data['code'] = $code ? $code : '';
                $data['name'] = $name;
                $result = model('Expresscompany')->where(['id'=>$id])->update($data);
                if(false === $result)
                {
                    return ['error'=>['msg'=>'操作失败']];
                }else{
                    return ['msg'=>'操作成功'];
                }
            }
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 删除
     */
    public function del($id)
    {
        if($id)
        {
            $isset = model('Expresscompany')->where(['id'=>$id,'is_del'=>0])->count();
            if($isset)
            {
                $result = model('Expresscompany')->where(['id'=>$id])->update(['is_del'=>1]);
                if(false === $result)
                {
                    return ['error'=>['msg'=>'操作失败']];
                }else{
                    return ['msg'=>'操作成功'];
                }
            }else{
                return ['error'=>['msg'=>'该记录不存在或已删除']];
            }
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 启用修改
     */
    public function turn($id)
    {
        if(!$id)
        {
            return ['error'=>['msg'=>'操作失败']];
        }
        $isset = model('Expresscompany')->where(['id'=>$id,'is_del'=>0])->count();
        if(!$isset)
        {
            return ['error'=>['msg'=>'该公司不存在或已删除']];
        }
        $is_use = input('param.turn');
        $result = model('Expresscompany')->where(['id'=>$id])->update(['is_use'=>$is_use]);
        if(false === $result)
        {
            return ['error'=>['msg'=>'操作失败']];
        }
        return ['msg'=>'操作成功'];
    }

}