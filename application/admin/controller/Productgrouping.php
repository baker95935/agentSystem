<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Productgrouping extends Controller
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
        $grouping = model('Productgrouping');
        $data=array();
        if(!empty($name)){
            $data['grouping_name']=['like','%'.$name.'%'];//加搜索条件
        }
        $groupingList=$grouping->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));

        $this->assign('groupingList',$groupingList);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $grouping = model('Productgrouping');

        $request = request();
        $id=$request->param('id');

        $data=array();
        !empty($id) && $data=$grouping::get($id);

        $this->assign('data',$data);
        return $data['grouping_name'];
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
        $grouping = model('Productgrouping');

        if($request->method()=='POST') {
            //数据获取
            $data=array(
                'grouping_name'=>$request->param('name'),
                'id'=>$request->param('id'),
            );
            $gName=$grouping::get(['grouping_name'=>$data['grouping_name']]);
            
            if(empty($gName))
            {
                $result=0;
                if(empty($data['id'])){//添加
                    $data['create_time']=time();
                    $result=$grouping->save($data);
                }else{//更新
                    $result=$grouping->save($data,array('id'=>$data['id']));
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
                $msg='组名重复，请重新输入！';
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
    public function delete($id)
    {
        $menu=model('Productgrouping');
        $request = request();
        $result=0;
        $data=null;

            $id=$request->param('id');
            //字符串转换成数组
            $str = explode(" ",$id);
            //循环删除
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
