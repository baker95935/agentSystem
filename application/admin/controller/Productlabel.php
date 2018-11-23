<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Productlabel extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $request = request();
        $name=$request->param('name');
        $label = model('Productlabel');
        $data=array();
        if(!empty($name)){
            $data['product_name']=['like','%'.$name.'%'];//加搜索条件
        }
        $labelList=$label->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));

        $this->assign('labelList',$labelList);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $label = model('Productlabel');

        $request = request();
        $id=$request->param('id');

        $data=array();
        !empty($id) && $data=$label::get($id);

        $this->assign('data',$data);
        return $data['product_name'];
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
        $menu = model('Productlabel');

        if($request->method()=='POST') {
            //数据获取
            $data=array(
                'product_name'=>$request->param('name'),
                'id'=>$request->param('id'),
            );
            $lName=$menu::get(['product_name'=>$data['product_name']]);
            if(empty($lName)){
                $result=0;
                if(empty($data['id'])){//添加
                    $data['create_time']=time();
                    $result=$menu->save($data);
                } else {
                    $result=$menu->save($data,array('id'=>$data['id']));//更新
                }
                if($result){
                    $code=1;
                    $msg='成功';
                }else{
                    $code=0;
                    $msg='失败';
                }
            }else{
                $code=-1;
                $msg='标签重复，请重新输入！'; 
            }
            return  json_encode(['code'=>$code,'msg'=>$msg]);
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $id=$request->param('id');
        //字符串转换成数组
        $str = explode(" ",$id);
        $menu = model('Productlabel');
        $result=0;
        foreach ($str as $k=>$value){
            $result=$menu->destroy($value);
        }

        if($result){
            $data['status']='ok';
        }else{
            $data['status']='no';
        }
        return json_encode($data);
    }
}
