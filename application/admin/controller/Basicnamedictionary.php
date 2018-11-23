<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Basicnamedictionary extends Controller
{
    /**
     * 显示名称字典列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$request = request();
    	$name=$request->param('name');
    	 
    	$data=array();
    	!empty($name) && $data['name']=['like','%'.$name.'%'];
    	 
    	$dictionary = model('Basicnamedictionary');
    	$dictionaryList=$dictionary->where($data)->order('id', 'desc')->paginate(config('paginate.list_rows'));
    	$this->assign('dictionaryList',$dictionaryList);
    	
    	
    	return $this->fetch();
    }

    /**
     * 显示创建名称字典表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
    	$dictionary = model('Basicnamedictionary');
    	 
    	$request = request();
    	$id=$request->param('id');
    	 
    	$data=array();
    	!empty($id) && $data=$dictionary::get($id);
    	$this->assign('data',$data);
    	 
    	return $this->fetch(); 
    }

    /**
     * 保存名称字典
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
    	$user = model('Basicnamedictionary');
    	
    	if($request->method()=='POST') {
    		//数据获取
    		$data=array(
    				'name'=>$request->param('name'),
    				'value'=>$request->param('value'),
    				'id'=>$request->param('id'),
    		);
    		
    		//数据校验
    		$validate = validate('Basicnamedictionary');
    			
    		//添加和编辑验证的场景不同
    		$res=$validate->check($data);
    			
    		if(!$res){
    			$this->error($validate->getError());
    		} else {
    				
    			$result=0;
    			if(empty($data['id'])){//添加
    				$data['create_time']=time();
    				$result=$user->save($data);
    			} else {
    				$data['update_time']=time();
    				$result=$user->save($data,array('id'=>$data['id']));//更新
    			}
    				
    			$this->success('操作成功', '/admin/Basicnamedictionary/index/');
    				
    		}
    	
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
    	$dictionary=model('Basicnamedictionary');
    	$request = request();
    	 
    	if($request->method()=='GET') {
    		 
    		$id=$request->param('id');
    
    		$result=0;
    		$result=$dictionary->destroy($id);
    		 
    		if($result==0){
    			$this->success('操作失败，请重试');
    		} else {
    			$this->success('操作成功', '/admin/Basicnamedictionary/index/');
    		}
    	}
    	 
    	$this->success('非法操作，请重试');
    }
}
