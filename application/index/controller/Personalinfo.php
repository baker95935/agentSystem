<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Personalinfo extends Controller
{
    protected $beforeActionList = [
        'first' => ['only'=>'personalinfolist,personalinfo'],
    ];

    protected function first()
    {
        $user = session('user');
        $tip = '';
        if($user['role'] <= 0)
        {
            // if ($user['status'] == 1)
            // {
            //     $tip = 2;
            // }else{
                $tip = 1;
            // }
        }
        $this->assign('tip',$tip);
    }

    /**
     * 未登录的跳转登录页
     */
    protected function checkLogin()
    {
        if(!session('?user'))
        {// 未登录
            $this->redirect('Index/login');
        }
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function personalInfo()
    {
        $this->checkLogin();
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');

        $Agentslist = $Agents->where('agent_id',$user['a_id'])->find();

        $this->assign('Agentslist',$Agentslist);
        $this->assign('spider',['title'=>'个人资料','content'=>'','key'=>'']);
        return $this->fetch('personalInfo/personalInfo');
    }

    public function saveAll()
    {
        //操作代理商表
        $Agents  = model('Agents');
        $user    = session('user');
        $info    = $Agents->where('agent_id',$user['a_id'])->find();
        $datamsg = array();
        $datamsg['qq']       = $info['qq'];
        $datamsg['sex']      = $info['sex'];
        $datamsg['identity'] = $info['id_card'];
        $datamsg['province'] = $info['province'];
        $datamsg['city']     = $info['city'];
        $datamsg['area']     = $info['area'];
        $datamsg['address']  = $info['address'];
        $datamsg['nickname'] = $info['nickname'];
        $datamsg['name']     = $info['name'];
        $received = request();
        //更新微信昵称
        $nickname = $received->param('wechatName');
        if(!empty($nickname)){
            if ($nickname == $datamsg['nickname']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $result = $Agents->where('agent_id',$user['a_id'])->update(['nickname'=>$nickname]);
                if(false !== $result)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
        //更新微信号
        $wechatID = $received->param('wechatID');
        if(!empty($wechatID)){
            $succeed_id = $Agents->where('agent_id',$user['a_id'])->update(['wechat'=>$wechatID]);
            if (false !== $succeed_id)
            {
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                return ['error'=>['msg'=>'更新失败']];
            }
        }
        //更新QQ号
        $qq = $received->param('qq');
        if (!empty($qq)){
            if($qq == $datamsg['qq']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $succeed_qq = $Agents->where('agent_id',$user['a_id'])->update(['qq'=>$qq]);
                if (false !== $succeed_qq)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
        //更新姓名
        $myName = $received->param('myName');
        if (!empty($myName)){
            if ($myName == $datamsg['name']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $succeed_name = $Agents->where('agent_id',$user['a_id'])->update(['name'=>$myName]);
                if (false !== $succeed_name)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
        //更新性别
        $sex_m_w = $received->param('sex_m_w');
        if (!empty($sex_m_w))
        {
            if ($sex_m_w ==  $datamsg['sex']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $succeed_sex = $Agents->where('agent_id',$user['a_id'])->setField(['sex'=>$sex_m_w]);
                if (false !== $succeed_sex)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
        //更新身份证
        $identity = $received->param('identity');
        if (!empty($identity))
        {
            if ($identity==$datamsg['identity']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $succeed_identity = $Agents->where('agent_id',$user['a_id'])->setField(['id_card'=>$identity]);
                if (false !== $succeed_identity)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
        //更新地址
        $address = $received->param('address');//详细地址
        if (!empty($citydata)){
            $address_data = explode(" ",$citydata);//分割字符串
            $address_field =array('province','city','area');//数据库字段
            $data_list = array_combine($address_field,$address_data);//结合数据信息
        }
        $data['province'] = model('BasicDataAddress')->getCityIdByName($received->param('sheng'));
        $data['city']     = model('BasicDataAddress')->getCityIdByName($received->param('shi'));
        $data['area']     = model('BasicDataAddress')->getCityIdByName($received->param('qu'));
        $data['address']  = $address;//详细地址
        //判断是否是第一次提交数据
        $agents_address = $Agents->where('agent_id',$user['a_id'])->value('address');

        $name = $Agents->where('agent_id',$user['a_id'])->value('name');
        empty($name) && $name="姓名";
        if(empty($agents_address)) {
            $ndata=array();
            $ndata['name']       = $name;
            $ndata['phone']      = $user['phone'];
            $ndata['province']   = $data['province'];
            $ndata['city']       = $data['city'];
            $ndata['area']       = $data['area'];
            $ndata['address']    = $data['address'];
            $ndata['a_id']       = $user['a_id'];
            $ndata['is_default'] = 1;
            model('AgentAddress')->save($ndata);

        }
        if (!empty($data))
        {
            if ($data['province'] ==$datamsg['province']&&$data['city']==$datamsg['city']&&$data['area']==$datamsg['area']&&$data['address']==$datamsg['address']){
                return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
            }else{
                $succeed_address = $Agents->where('agent_id',$user['a_id'])->setField($data);
                if (false !== $succeed_address)
                {
                    return ['msg'=>'更新成功','url'=>url('Personalinfo/personalInfo')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
    }

    //微信名称
    public function WechatNickName()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
         $data=$Agents::get($user['a_id']);
          $this->assign('data',$data);
        return $this->fetch('personalInfo/WechatNickName');
    }
    //微信ID
    public function WechatID()
    {
        $this->checkLogin();
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);
        $openid = model('Weixinusers')->where(['agent_id'=>$user['a_id']])->column('openid');
        $data['openid'] = $openid ? $openid[0] : '';
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'微信号']);
        return $this->fetch('personalInfo/WechatID');
    }
    //QQ
    public function myQQ()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);
        $this->assign('data',$data);
        return $this->fetch('personalInfo/myQQ');
    }
    //姓名
    public  function myName()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);
        $this->assign('data',$data);
        return $this->fetch('personalInfo/myName');

    }
    public function sex()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);

        $this->assign('data',$data);
        return $this->fetch('personalInfo/sex');
    }
    public function identity()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);
        $this->assign('data',$data);
        return $this->fetch('personalInfo/identity');
    }
    public function address()
    {
        $user = session('user');
        //操作代理商表
        $Agents=model('Agents');
        $data=$Agents::get($user['a_id']);
        $this->assign('data',$data);
        return $this->fetch('personalInfo/address');
    }

    public function personalInfoList()
    {
        $this->assign('spider',['title'=>'个人信息','content'=>'','key'=>'']);
        return $this->fetch('personalInfo/personalInfoList');
    }
    //上传头像
    public function uploads()
    {
        //操作代理商表
        $Agents=model('Agents');
        $user = session('user');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('up_img');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!empty($file)){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                $filePath = '/uploads/'.$info->getSaveName();
                if (!empty($filePath)){

                    $Agents->where('agent_id',$user['a_id'])->update(['head_img'=>$filePath]);
                    session('user.head_img',$filePath);
                    $data['state']= 1;
                    $data['savedir']= $filePath;
                    return json_encode($data,JSON_UNESCAPED_SLASHES);
                }else{
                    $data['state']= 0;
                    return json_encode($data);
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    //上传二维码
    public function upload_QR_code()
    {
        //操作代理商表
        $Agents=model('Agents');
        $user = session('user');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('QR_code_img');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!empty($file)){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                $filePath = '/uploads/'.$info->getSaveName();
                if (!empty($filePath)){
//                    var_dump($filePath);die;
                    $Agents->where('agent_id',$user['a_id'])->update(['qr_code_img'=>$filePath]);
                    $data['state']= 1;
                    $data['savedir']= $filePath;
                    return json_encode($data,JSON_UNESCAPED_SLASHES);
                }else{
                    $data['state']= 0;
                    return json_encode($data);
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    //授权证书
    public function accredit()
    {
        //操作代理商表
        $Agents=model('Agents');
        $user = session('user');
        $accreditinfo = $Agents->where('agent_id',$user['a_id'])->select();
        $accreditdata=array();
        foreach ($accreditinfo as $k=>&$v){
            $accreditdata[$k]['name']=$v['name'];
            $accreditdata[$k]['id_card']=$v['id_card'];
            $accreditdata[$k]['wechat']=$v['wechat'];
            $accreditdata[$k]['create_year']= date('Y',strtotime($v['create_ctime']));
            $accreditdata[$k]['create_month']= date('m',strtotime($v['create_ctime']));
            $accreditdata[$k]['create_day']= date('d',strtotime($v['create_ctime']));
            if ($v['end_etime']==-1){
                $accreditdata[$k]['end_year'] = '-';
                $accreditdata[$k]['end_month'] = '-';
                $accreditdata[$k]['end_day'] = '-';
            }else{
                $accreditdata[$k]['end_year'] = date('Y',strtotime($v['end_etime']));
                $accreditdata[$k]['end_month'] = date('m',strtotime($v['end_etime']));
                $accreditdata[$k]['end_day'] = date('d',strtotime($v['end_etime']));
            }
        }
        $this->assign('accreditdata',$accreditdata);
        $this->assign('spider',['title'=>'授权证书','content'=>'','key'=>'']);
        return $this->fetch('personalInfo/accredit');
    }
}
