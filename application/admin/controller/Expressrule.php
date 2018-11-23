<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Expressrule extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $received = request();
        $list = model('Expressrule')->RuleListProvinceSwitch($received->param());
        $this->assign('dictionaryList',$list);
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $all = model('BasicDataAddress')->getAllList();// 分级获取全国城市
        $this->assign('provinceList',$all[0]);
        $this->assign('cityList',$all[1]);
        $this->assign('areaList',$all[2]);
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
        $data['name']           = $received->param('name');
        $data['type']           = $received->param('type');
        $data['first_num']      = $received->param('FirstNum');
        $data['continue_num']   = $received->param('ContinueNum');
        $data['free_num']       = $received->param('FreeNum');
        $data['first_price']    = $received->param('price');// 首件价
        $data['continue_price'] = $received->param('ContinuePrice');
        $data['cost']           = $received->param('cost');
        $data['is_inside']      = $received->param('include');// 区域内|外
        $data['province']       = $received->param('checked_province');
        $data['city']           = $received->param('checked_city');
        $data['area']           = $received->param('checked_area');
        $data['create_ctime']   = date('Y-m-d H:i:s');
        $name_repeat = model('Expressrule')->checkNameIsRepeat($data['name']);
        if($name_repeat)
        {
            return ['error'=>['msg'=>'名称已存在，请重新修改']];
        }
        $result = model('Expressrule')->insert($data);
        if(false !== $result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 删除运费规则记录
     * @return [type] [description]
     */
    public function del()
    {
        $received = request();
        $id = $received->param('did','');
        if(!empty($id))
        {
            $state = model('Expressrule')->delUpdate($id);
            if($state)
            {
                return ['msg'=>'操作成功'];
            }else{
                return ['error'=>['msg'=>'操作失败']];
            }
        }else{
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
        if ($id)
        {
            $info = model('Expressrule')->where(['is_del'=>0,'id'=>$id])->find();
            if(!$info)
            {
                $this->redirect('index');
            }
            if(!empty($info['province']))
            {
                $info['p_str'] = model('BasicDataAddress')->getNamesByIds($info['province']);
            }
            if(!empty($info['city']))
            {
                $info['c_str'] = model('BasicDataAddress')->getNamesByIds($info['city']);
            }
            if(!empty($info['area']))
            {
                $info['a_str'] = model('BasicDataAddress')->getNamesByIds($info['area']);
            }
            $this->assign('info',$info);
            $all = model('BasicDataAddress')->getAllList();// 分级获取全国城市
            $this->assign('provinceList',$all[0]);
            $this->assign('cityList',$all[1]);
            $this->assign('areaList',$all[2]);
            return $this->fetch('modify');
        } else {
            $this->redirect('index');
        }
    }

    /**
     * 保存更新的资源
     *
     * @return \think\Response
     */
    public function update()
    {
        $received = request();
        $mid = $received->param('id');// 修改ID
        if ($mid)
        {
            $data['name']           = $received->param('name');
            $data['type']           = $received->param('type');
            $data['first_num']      = $received->param('FirstNum');
            $data['continue_num']   = $received->param('ContinueNum');
            $data['free_num']       = $received->param('FreeNum');
            $data['first_price']    = $received->param('price');// 首件价
            $data['continue_price'] = $received->param('ContinuePrice');
            $data['cost']           = $received->param('cost');
            $data['is_inside']      = $received->param('include');// 区域内|外
            $data['province']       = $received->param('checked_province');
            $data['city']           = $received->param('checked_city');
            $data['area']           = $received->param('checked_area');
            $name_repeat = model('Expressrule')->checkNameIsRepeat($data['name'],$mid);
            if($name_repeat)
            {
                return ['error'=>['msg'=>'名称已存在，请重新修改']];
            }
            $result = model('Expressrule')->where(['id'=>$mid])->update($data);
            if (false !== $result)
            {
                return ['msg'=>'操作成功'];
            }else{
                return ['error'=>['msg'=>'操作失败']];
            }
        } else {
            return ['error'=>['msg'=>'参数错误']];
        }
    }

    /**
     * 编辑页-地区关键字搜索功能
     */
    public function ajaxSearchCity()
    {
        $received    = request();
        $m_basicdata = model('BasicDataAddress');
        $search_key['name'] = $received->param('key');// 关键字
        /* 首先在省级检索 */
        $p = $m_basicdata->searchCityId($search_key);
        if($p)
        { /* 获取该省的第一个市 */
            $p_son = $m_basicdata->searchCityId(['parent_id'=>$p['id']],2);
            if($p_son)
            {
                return ['p'=>$p['id'],'c'=>$p_son['id']];
            }else{
                return ['p'=>$p['id']];
            }
        }
        /* 其次在市级检索 */
        $c = $m_basicdata->searchCityId($search_key,2);
        if($c)
        { /* 获取该市的上级省 */
            return ['p'=>$c['parent_id'],'c'=>$c['id']];
        }
        /* 再次在区级检索 */
        $a = $m_basicdata->searchCityId($search_key,3);
        if($a)
        { /* 获取该区的上级市 */
            $a_parent = $m_basicdata->searchCityId(['id'=>$a['parent_id']],2);
            return ['p'=>$a_parent['parent_id'],'c'=>$a_parent['id']];
        }
        return ['error'=>['msg'=>'查无结果']];
    }
}