<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\admin\model\Agentlevel as AgentLevelModel;
use app\admin\model\Agentrewardagency as AdminRewardAgency;
use app\admin\model\WithdrawalsLog as AdminWithdrawalsLog;
use app\admin\model\Agentrewardconfig as AdminRewardConfig;
use app\admin\model\Agentperformancereward as AdminPerformanceReward;
use app\admin\model\Agentrewardperformance as AdminRewardPerformance;
use think\Route as route;

class Person extends Controller
{
    protected $beforeActionList = [
        'first' => ['only'=>'index,myassets,myprofit,profitlog,myprofit,myincome,accountsecurity,promotionreward'],
    ];

    /**
     * 权限判断
     */
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
     * 未登录的跳转登录页(done)
     */
    protected function checkLogin()
    {
        if(!session('?user'))
        {// 未登录
            $this->redirect('Index/login');
        }
    }

    /**
     * 个人中心(done)
     */
    public function index()
    {
        $this->checkLogin();
        $user = session('user');
        // 获取代理头像
        $Agents = model('Agents');
        $data = $Agents::get($user['a_id']);
        $this->assign('data',$data);
        $m_agentLevel = new AgentLevelModel();
        $role_lang = $m_agentLevel->getNameById($user['role']);// 代理等级
        $this->assign('role_lang',$role_lang);
        $this->assign('spider',['title'=>'个人中心','content'=>'','key'=>'','index'=>5]);
        return $this->fetch();
    }

    /**
     * 账户安全(done)
     */
    public function accountSecurity()
    {
        $this->checkLogin();
        $this->assign('spider',['title'=>'账号安全','content'=>'','key'=>'']);
        return $this->fetch('accountSecurity');
    }

    /**
     * 账户安全-修改手机号(登录账号)(done)
     */
    public function modifyPhone()
    {
        $this->checkLogin();
        $this->assign('spider',['title'=>'修改手机号','content'=>'','key'=>'']);
        return $this->fetch('modifyPhone');
    }

    /**
     * 账户安全-修改手机号-获取验证码(done)
     */
    public function getPhoneCode()
    {
        if(!session('?user'))
        {// 未登录
            return ['error'=>['msg'=>'你还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $user = session('user');
        $phone = $user['phone'];
        $code = get_mt_rand();// 验证码
        session('ChangePhone_'.$user['a_id'].'_'.$phone,[$code,time()+180]);// 保存到session(验证码,3分钟有效时间)
        $return = send_sms(['phone'=>$user['phone'],'code'=>$code,'template'=>'SMS_86815036']);// 发送验证码
        if($return['Code'] == 'OK')
        {
            return ['msg'=>'ok'];
        }else{
            return ['error'=>['msg'=>'发送失败，请联系我们']];
        }
    }

    /**
     * 账户安全-修改手机号-校验验证码(done)
     */
    public function checkCode()
    {
        if(!session('?user'))
        {// 未登录
            return ['error'=>['msg'=>'你还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $received = request();
        $code = $received->param('code');
        $user = session('user');
        $session_code = session('ChangePhone_'.$user['a_id'].'_'.$user['phone']);
        $now = time();
        if(isset($session_code) && isset($code) && $code == $session_code[0] && $now <= $session_code[1])
        {
            session('ChangePhone_'.$user['a_id'].'_'.$user['phone'],null);// 清除
            session('AgreeModPhone',time()+600);// 允许修改手机号标识10分钟内有效
            return ['msg'=>'ok','url'=>url('Person/modifyPhoneNext')];
        }else{
            if($now > $session_code[1])
            {
                return ['error'=>['msg'=>'验证码已失效，请重新获取']];
            }else{
                return ['error'=>['msg'=>'验证码错误']];
            }
        }
    }

    /**
     * 修改手机号-第二步(done)
     */
    public function modifyPhoneNext()
    {
        $this->checkLogin();
        $now = time();
        $session_time = session('AgreeModPhone');
        if($now > $session_time)
        {// 超过有效时间
            $this->assign('invalid',1);
            $this->redirect('Person/modifyPhone');
        }else{
            $this->assign('invalid',0);
        }
        $this->assign('spider',['title'=>'设置新手机号','content'=>'','key'=>'']);
        return $this->fetch('modifyPhoneNext');
    }

    /**
     * 修改手机号-第二步-获取验证码(done)
     */
    public function getPhoneCodeNext()
    {
        if(!session('?user'))
        {// 未登录
            return ['error'=>['msg'=>'你还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $user = session('user');
        $received = request();
        $phone = $received->param('phone');
        if(isset($phone))
        {
            $isset_log = model('Agents')->where(['phone'=>$phone,'is_del'=>0])->count();// 检查记录
            if($isset_log >= 1)
            {
                return ['error'=>['msg'=>'该手机号已绑定账号不能重复绑定，请更换其他手机号码']];
            }else{
                $code = get_mt_rand();// 验证码
                session('NewPhone_'.$user['a_id'].'_'.$phone,[$code,time()+180]);// 保存到session(验证码,3分钟有效时间)
                $return = send_sms(['phone'=>$phone,'code'=>$code,'template'=>'SMS_86815042']);// 发送验证码
                if($return['Code'] == 'OK')
                {
                    return ['msg'=>'ok'];
                }else{
                    return ['error'=>['msg'=>'发送失败，请联系我们']];
                }
            }
        }else{
            return ['error'=>['msg'=>'新手机号错误']];
        }
    }

    /**
     * 修改手机号-第二步-完成更改(done)
     */
    public function doModifyPhone()
    {
        if(!session('?user'))
        {// 未登录
            return ['error'=>['msg'=>'你还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $received = request();
        $user = session('user');
        $phone = $received->param('phone');
        $code = $received->param('code');
        $now = time();
        if(isset($phone) && isset($code))
        {
            $session_code = session('NewPhone_'.$user['a_id'].'_'.$phone);
            if($now <= $session_code[1])
            {
                if ($code == $session_code[0])
                {
                    session('NewPhone_'.$user['a_id'].'_'.$phone,null);// 清除code
                    $result = model('Agents')->save(['phone'=>$phone],['agent_id'=>$user['a_id']]);
                    if ($result)
                    {
                        session('user.phone',$phone);// 更新手机号
                        session('AgreeModPhone',null);// 清除允许标记
                        return ['msg'=>'操作成功','url'=>url('Person/accountSecurity')];
                    } else {
                        return ['error'=>['msg'=>'操作失败']];
                    }
                } else {
                    return ['error'=>['msg'=>'验证码错误，请重试']];
                }
            }else{
                return ['error'=>['msg'=>'验证码已失效，请重新获取']];
            }
        }else{
            return ['error'=>['msg'=>'参数错误，请提交有效数据']];
        }
    }

    /**
     * 账户安全-修改登录密码(done)
     */
    public function modifyPassword()
    {
        $this->checkLogin();
        $this->assign('spider',['title'=>'修改登录密码','content'=>'','key'=>'']);
        return $this->fetch('modifyPassword');
    }

    /**
     * 保存-修改登录密码(done)
     */
    public function savePassword()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();
        $password = $received->param('pwd');
        $result = model('Agents')->save(['password'=>md5(md5(trim($password)))],['agent_id'=>$user['a_id']]);
        if($result)
        {
            return ['msg'=>'修改成功'];
        }else{
            return ['error'=>['msg'=>'新密码不能与旧密码一致']];
        }
    }

    /**
     * 账户安全-资金账户(done)
     */
    public function financialAccount()
    {
        $this->checkLogin();
        $user = session('user');
        $wechat_is_bind = model('Weixinusers')->getUserOpenid($user['a_id']);// 微信是否绑定
        $bind_type = model('FinancialAccount')->getAgentFinancialState($user['a_id']);
        $this->assign('spider',['title'=>'资金账户','content'=>'','key'=>'']);
        $this->assign('bind',$bind_type);
        $this->assign('wechat_is_bind',$wechat_is_bind);
        return $this->fetch('financialAccount');
    }

    /**
     * 账户安全-资金账户-支付宝(done)
     */
    public function bindAlipay()
    {
        $this->checkLogin();
        $received = request();
        $choice = $received->param('choice');
        $choice = $choice ? $choice : 0;
        $user = session('user');
        $info = model('FinancialAccount')->field('account')->where(['a_id'=>$user['a_id'],'type'=>1,'is_del'=>0])->find();
        $this->assign('info',$info);
        $this->assign('choice',$choice);
        $this->assign('spider',['title'=>'支付宝账号','content'=>'','key'=>'']);
        return $this->fetch('bindAlipay');
    }

    /**
     * 账户安全-资金账户-银行卡(done)
     */
    public function bindBank()
    {
        $this->checkLogin();
        $received = request();
        $choice = $received->param('choice');
        $choice = $choice ? $choice : 0;
        $user = session('user');
        $info = model('FinancialAccount')->field('account,bank,name')->where(['a_id'=>$user['a_id'],'type'=>2,'is_del'=>0])->find();
        $this->assign('info',$info);
        $this->assign('choice',$choice);
        $this->assign('spider',['title'=>'银行卡账号','content'=>'','key'=>'']);
        return $this->fetch('bindBank');
    }

    /**
     * 账户安全-资金账户-微信支付
     */
    public function bindWechat()
    {
        $this->checkLogin();
        $user = session('user');
        $received = request();
        $choice = $received->param('choice');
        $choice = $choice ? $choice : 0;
        $info = model('FinancialAccount')->field('account')->where(['a_id'=>$user['a_id'],'type'=>3,'is_del'=>0])->find();
        $this->assign('info',$info);
        $this->assign('choice',$choice);
        $this->assign('spider',['title'=>'微信支付账号','content'=>'','key'=>'']);
        return $this->fetch('bindWechat');
    }

    /**
     * 保存资金账户(done)
     */
    public function saveFinancialAccount()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $received = request();
        $user = session('user');
        $type = $received->param('tid');// 账户类型
        $account = $received->param('account','','trim');
        if(empty($account))
        {
            return ['error'=>['msg'=>'账户不能为空']];
        }
        $bank = $received->param('bank','','trim');
        $name = $received->param('name','','trim');
        switch ($type)
        {
            case '1':
                $data = [
                    'type' => 1,// 支付宝
                ];
                break;
            case '2':
                $data = [
                    'type' => 2,// 银行卡
                    'bank' => $bank,
                    'name' => $name,
                ];
                break;
            case '3':
                $data = [
                    'type' => 3,// 微信
                    'is_default' => 1,
                ];
                break;
            default:
                # code...
                break;
        }
        $data['a_id']         = $user['a_id'];
        $data['account']      = $account;
        $data['create_ctime'] = date('Y-m-d H:i:s');
        $log = model('FinancialAccount')->where(['a_id'=>$user['a_id'],'type'=>$type])->count();
        if($log)
        {
            $result = model('FinancialAccount')->save($data,['a_id'=>$user['a_id'],'type'=>$type]);// 保存
        }else{
            $result = model('FinancialAccount')->insert($data);// 保存
        }
        if($result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 邀请代理(done)
     */
    public function invite()
    {
        $this->checkLogin();
        $user = session('user');
        $path = "uploads/qrcode/";// 保存路径
        if(!file_exists($path))
        {
            mkdir($path, 0700);
        }
        $filename = $user['a_id'].'_'.$user['phone'].'.png';// 文件名
        $save_file = $path.$filename;// 保存:png第二个参数
        if(!file_exists($save_file))
        {
            vendor('phpqrcode.phpqrcode');
            $QRcode = new \QRcode();
            $data = url('Index/register','phone='.$user['phone'],'html',true);// 二维码保存的信息
            $level = 'M';// 纠错级别：L、M、Q、H
            $point_size = 7;// 点的大小：1到10,用于手机端4就可以了
            $size = 2;// 空白大小
            ob_end_clean();//清空缓冲区
            $QRcode->png($data, $save_file, $level, $point_size, $size);
        }
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName();
        $this->assign('role_lang',$role_lang);
        $this->assign('img',$save_file);
        $this->assign('spider',['title'=>$user['role'] == 0 ? '我的二维码': '邀请代理','content'=>'','key'=>'']);
        $m_agent = model('Agents');
        $data = $m_agent::get($user['a_id']);
        $this->assign('data',$data);
        return $this->fetch('invite');
    }

    /**
     * 地址管理(done)
     */
    public function addressManage()
    {
        $this->checkLogin();
        $user = session('user');
        $list = model('AgentAddress')->field('id,name,phone,province,city,area,address,is_default')->where(['a_id'=>$user['a_id'],'is_del'=>0])->select();
        $this->assign('list',$list);
        $this->assign('spider',['title'=>'地址管理','content'=>'','key'=>'']);
        return $this->fetch('addressManage');
    }

    /**
     * 地址管理-设置默认地址(done)
     */
    public function setDefault()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();
        $add_id = $received->param('id');
        if(isset($add_id))
        {
            model('AgentAddress')->save(['is_default'=>0],['a_id'=>$user['a_id'],'is_del'=>0]);// 所有地址取消默认
            $result = model('AgentAddress')->save(['is_default'=>1],['a_id'=>$user['a_id'],'is_del'=>0,'id'=>$add_id]);// 设置默认
            if ($result)
            {
                return ['msg'=>'操作成功'];
            } else {
                return ['error'=>['msg'=>'操作失败']];
            }
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 添加新地址(done)
     */
    public function addAddress()
    {
        $this->checkLogin();
        $this->assign('spider',['title'=>'添加新地址','content'=>'','key'=>'']);
        return $this->fetch('addAddress');
    }

    /**
     * 添加新地址-保存(done)
     */
    public function saveAddAdd()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user  = session('user');
        $total = model('AgentAddress')->where(['a_id'=>$user['a_id'],'is_del'=>0])->count();// 已有未删除记录数
        if($total >= 10)
        {
            return ['error'=>['msg'=>'最多能保存十条地址信息']];
        }
        $received = request();
        $data = [
            'a_id'     => $user['a_id'],
            'name'     => $received->param('name'),
            'phone'    => $received->param('phone'),
            'address'  => $received->param('add'),
            'province' => $received->param('p'),
            'city'     => $received->param('c'),
            'area'     => $received->param('a'),
        ];
        $is_default = $received->param('df');// 是否默认
        if($is_default == 1)
        {
            $data['is_default'] = 1;
            model('AgentAddress')->save(['is_default'=>0],['a_id'=>$user['a_id'],'is_del'=>0]);// 取消其他地址的默认设置
        }
        /* 获取城市对应ID */
        $data['province'] = model('BasicDataAddress')->getCityIdByName($data['province']);
        $data['city']     = model('BasicDataAddress')->getCityIdByName($data['city']);
        $data['area']     = model('BasicDataAddress')->getCityIdByName($data['area']);
        $result = model('AgentAddress')->insert($data);// 保存
        if ($result)
        {
            return ['msg'=>'操作成功'];
        } else {
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 修改某个地址(done)
     */
    public function modAddress()
    {
        $this->checkLogin();
        $user = session('user');
        $received = request();
        $add_id = $received->param('id');
        if($add_id)
        {
            $info = model('AgentAddress')->field('id,name,phone,province,city,area,address')->where(['id'=>$add_id,'is_del'=>0])->find();
            if($info)
            {
                $this->assign('info',$info);
            }else{
                $this->redirect('addressManage');
            }
        }else{
            $this->redirect('addressManage');
        }
        $this->assign('spider',['title'=>'编辑地址','content'=>'','key'=>'']);
        return $this->fetch('modAddress');
    }

    /**
     * 修改某个地址-保存(done)
     */
    public function saveModAdd()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();
        $add_id = $received->param('id');
        if (isset($add_id))
        {
            $isset = model('AgentAddress')->where(['id'=>$add_id,'a_id'=>$user['a_id'],'is_del'=>0])->find();
            if ($isset)
            {
                $data = [
                    'name' => $received->param('name'),
                    'phone' => $received->param('phone'),
                    'province' => $received->param('p'),
                    'city' => $received->param('c'),
                    'area' => $received->param('a'),
                    'address' => $received->param('add'),
                ];
                /**/
                $data['province'] = model('BasicDataAddress')->getCityIdByName($data['province']);
                $data['city'] = model('BasicDataAddress')->getCityIdByName($data['city']);
                $data['area'] = model('BasicDataAddress')->getCityIdByName($data['area']);
                $result = model('AgentAddress')->save($data,['id'=>$add_id,'a_id'=>$user['a_id'],'is_del'=>0]);// 保存
                if ($result)
                {
                    return ['msg'=>'操作成功'];
                } else {
                    return ['error'=>['msg'=>'操作失败']];
                }
            } else {
                return ['error'=>['msg'=>'该记录不存在或已删除']];
            }
        } else {
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 删除某个地址(done)
     */
    public function delAddress()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();
        $add_id = $received->param('id');
        if (isset($add_id))
        {
            $isset = model('AgentAddress')->where(['id'=>$add_id,'is_del'=>0,'a_id'=>$user['a_id']])->find();
            if ($isset)
            {
                $result = model('AgentAddress')->save(['is_del'=>1],['id'=>$add_id,'a_id'=>$user['a_id']]);
                if ($result)
                {
                    return ['msg'=>'操作成功'];
                } else {
                    return ['error'=>['msg'=>'操作成功']];
                }
            } else {
                return ['error'=>['msg'=>'该记录不存在或已删除']];
            }
        } else {
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 我的资产(done)
     */
    public function myAssets()
    {
        $this->checkLogin();
        $user = session('user');
        
        $this->assign('user',$user);
        $this->assign('spider',['title'=>'我的资产','content'=>'','key'=>'']);
        return $this->fetch('myAssets');
    }

    /**
     * 我的收益(done)
     */
    public function myProfit()
    {
        $this->checkLogin();
        $user = session('user');
        $m_agent_order_reward = model('Agentorderreward');
        $m_agent = model('Agents');
        $m_stock_change = model('Agentstockchange');
        $now          = date('Y-m-01');
        $now_begin    = strtotime($now);// 本月开始
        $now_end      = strtotime($now.' + 1 month');// 本月结束
        $before_begin = strtotime($now.' - 1 month');// 上月开始
        $before_end   = $now_begin;// 上月结束
        $day_begin    = strtotime(date('Y-m-d'));// 当天开始
        $day_end      = strtotime(date('Y-m-d').' + 1 day');// 当天结束
        /* 总计 S */
        // 交易总额
        $sales_total       = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id']);
        // 订单数量
        $sales_order_total = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id']);

        // 累计收益(直接收益+间接收益)
        $profit_total      = model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>['in','6,2,7,8']]);
        /* 总计 E */
        /* 上月统计 S */
        // 上月直销金额
        $before_direct_money   = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$before_begin,$before_end],4);
        // 上月直销单数
        $before_direct_order   = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$before_begin,$before_end],4);
        // 上月间销金额
        $before_indirect_money = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$before_begin,$before_end],3);
        // 上月间销单数
        $before_indirect_order = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$before_begin,$before_end],3);
        /* 上月统计 E */
        /* 本月统计 S */
        // 本月直销金额
        $now_direct_money      = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$now_begin,$now_end],4);
        // 本月直销单数
        $now_direct_order      = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$now_begin,$now_end],4);
        // 本月间销金额
        $now_indirect_money    = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$now_begin,$now_end],3);
        // 本月间销单数
        $now_indirect_order    = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$now_begin,$now_end],3);
        /* 本月统计 E */
        /* 日销售额比例计算 S */
        // 日直销额
        $day_direct_sale    = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$day_begin,$day_end],4);
        // 日直销订单数
        $day_direct_order   = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$day_begin,$day_end],4);
        // 日间销额
        $day_indirect_sale  = $m_agent_order_reward->getSaleTotalByAgentId($user['a_id'],[$day_begin,$day_end],3);
        // 日间销订单数
        $day_indirect_order = $m_agent_order_reward->countSaleOrderTotalByAgentId($user['a_id'],[$day_begin,$day_end],3);

        // 总升级次数
        $update_time = $m_agent_order_reward->countSalesOrderByCondition(['agent_id'=>$user['a_id'],'reward_type'=>5,'`status`'=>2]);
        // 上月交易额
        $before_update_money = $m_agent_order_reward->getSalesAmountSumByCondition(['agent_id'=>$user['a_id'],'reward_type'=>5,'`status`'=>2,'create_time'=>['between',[$before_begin,$before_end]]]);
        // 本月交易额
        $now_update_money    = $m_agent_order_reward->getSalesAmountSumByCondition(['agent_id'=>$user['a_id'],'reward_type'=>5,'`status`'=>2,'create_time'=>['between',[$now_begin,$now_end]]]);
        // 上月升级次数
        $before_update_time  = $m_agent_order_reward->countSalesOrderByCondition(['agent_id'=>$user['a_id'],'reward_type'=>5,'`status`'=>2,'create_time'=>['between',[$before_begin,$before_end]]]);
        // 本月升级次数
        $now_update_time     = $m_agent_order_reward->countSalesOrderByCondition(['agent_id'=>$user['a_id'],'reward_type'=>5,'`status`'=>2,'create_time'=>['between',[$now_begin,$now_end]]]);

{ /* 下级充值库存情况统计 */

        // 上月下级充值库存总额
        $before_stock_money = $m_agent_order_reward->getSalesAmountSumByCondition(['agent_id'=>$user['a_id'],'reward_type'=>8,'`status`'=>2,'create_time'=>['between',[$before_begin,$before_end]]]);
        // 本月下级充值库存总额
        $now_stock_money    = $m_agent_order_reward->getSalesAmountSumByCondition(['agent_id'=>$user['a_id'],'reward_type'=>8,'`status`'=>2,'create_time'=>['between',[$now_begin,$now_end]]]);
        // 上月下级充值库存次数
        $before_stock_time  = $m_agent_order_reward->countSalesOrderByCondition(['agent_id'=>$user['a_id'],'reward_type'=>8,'`status`'=>2,'create_time'=>['between',[$before_begin,$before_end]]]);
        // 本月下级充值库存次数
        $now_stock_time     = $m_agent_order_reward->countSalesOrderByCondition(['agent_id'=>$user['a_id'],'reward_type'=>8,'`status`'=>2,'create_time'=>['between',[$now_begin,$now_end]]]);
}

        $day_money_total  = $day_direct_sale + $day_indirect_sale;
        $direct_percent   = $day_money_total == 0 ? 0: round($day_direct_sale/$day_money_total,2);
        $indirect_percent = $day_money_total == 0 ? 0: 1-$direct_percent;
        $day_order_total        = $day_direct_order + $day_indirect_order;
        $direct_order_percent   = $day_order_total == 0 ? 0: round($day_direct_order/$day_order_total,2);
        $indirect_order_percent = $day_order_total == 0 ? 0: 1-$direct_order_percent;

        /* 日销售额比例计算 E */
        $data['sales_total']           = $sales_total;// 交易总额
        $data['sales_order_total']     = $sales_order_total;// 订单量
        $data['profit_total']          = $profit_total;// 累计收益
        $data['before_direct_money']   = $before_direct_money;// 上月直销金额
        $data['now_direct_money']      = $now_direct_money;// 本月直销金额
        $data['before_direct_order']   = $before_direct_order;// 上月累计订单量
        $data['now_direct_order']      = $now_direct_order;// 本月累计订单量
        $data['before_indirect_money'] = $before_indirect_money;// 上月间接销售金额
        $data['now_indirect_money']    = $now_indirect_money;// 本月间接销售金额
        $data['before_indirect_order'] = $before_indirect_order;// 上月累计订单数
        $data['now_indirect_order']    = $now_indirect_order;// 本月累计订单数
        $data['day_money_total']       = $day_money_total;// 日销售额

        $data['update_time']           = $update_time;
        $data['before_update_money']   = $before_update_money;
        $data['now_update_money']      = $now_update_money;
        $data['before_update_time']    = $before_update_time;
        $data['now_update_time']       = $now_update_time;

        $data['before_stock_money']    = $before_stock_money;
        $data['now_stock_money']       = $now_stock_money;
        $data['before_stock_time']     = $before_stock_time;
        $data['now_stock_time']        = $now_stock_time;
        if($day_money_total == 0)
        {
            $data['money_percent'] = [];
        }elseif($direct_percent == 1 || $indirect_percent == 1){
            $data['money_percent'] = [
                ['item'=>$direct_percent == 1 ? '直销金额' : '间接销售金额' ,'count'=>1]
            ];
        }else{
            $data['money_percent'] = [
                ['item'=>'直销金额','count'=>$direct_percent],
                ['item'=>'间接销售金额','count'=>$indirect_percent]
            ];
        }
        if($day_order_total == 0)
        {
            $data['order_percent'] = [];
        }elseif($direct_order_percent == 1 || $indirect_order_percent == 1){
            $data['order_percent'] = [
                ['item'=>$direct_order_percent == 1 ? '直销订单' : '间接订单' ,'count'=>1]
            ];
        }else{
            $data['order_percent'] = [
                ['item'=>'直销订单','count'=>$direct_order_percent],
                ['item'=>'间接订单','count'=>$indirect_order_percent]
            ];
        }
        $data['money_percent'] = json_encode($data['money_percent']);
        $data['order_percent'] = json_encode($data['order_percent']);
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'我的收益','content'=>'','key'=>'']);
        return $this->fetch('myProfit');
    }

    /**
     * 我的收益-收益记录(done)
     */
    public function profitLog($search='',$type=0)
    {
        $this->checkLogin();
        $user = session('user');
        $m_reward_agency = new AdminRewardAgency();
        $percent = $m_reward_agency->where(['role'=>$user['role']])->find();
        $ratio = (100-$percent['ratio'])/100;// 享受折扣
        $where = ['p.agent_id'=>$user['a_id']];
        if(isset($search) && !empty($search))
        {
            $where['p.order_number'] = ['like','%'.$search.'%'];
        }
        $all_where = $direct_where = $indirect_where = $update_where = $charge_where = $where;
        $all_where['p.type']      = ['in','2,6,7,8'];
        $direct_where['p.type']   = 6;
        $indirect_where['p.type'] = 2;
        $update_where['p.type'] = 7;
        $charge_where['p.type'] = 8;

        $all      = model('Agentprofit')->getRewardLogsByType($all_where);// 全部
        foreach($all as $k=>$v) {
        	//加上招商奖励
        	$recommendReward=model('Agentprofit')->where("relation_id='".$v['relation_id']."' and type=3")->value('profit');
        	$v['profit']=$v['profit']+$recommendReward;
        }

        $direct   = model('Agentprofit')->getRewardLogsByDefined($direct_where,'p.order_number,p.profit,p.type,p.sales_amount,o.agent_id,o.create_time,o.pprice,o.pnumber,o.trans_expenses');// 直销
        $indirect = model('Agentprofit')->getRewardLogsByDefined($indirect_where,'p.order_number,p.profit,p.type,p.sales_amount,o.agent_id,o.create_time,o.pprice,o.pnumber,o.trans_expenses');// 间销
        $update = model('Agentprofit')->getRewardLogsByType($update_where);// 下级升级

{// 下级充值收益记录

        // 下级充值库存记录
        $charge = model('Agentprofit')->getRewardLogsByType($charge_where);// 下级充值
        foreach($charge as $sc_k=>$sc_v)
        {
            //加上招商奖励
            $recommendReward=model('Agentprofit')->where("relation_id='".$sc_v['relation_id']."' and type=3")->value('profit');
            $sc_v['profit']=$sc_v['profit']+$recommendReward;
        }
}

        foreach($update as $k=>$v) {
        	//加上招商奖励
        	$recommendReward=model('Agentprofit')->where("relation_id='".$v['relation_id']."' and type=3")->value('profit');
        	$v['profit']=$v['profit']+$recommendReward;
        }

        $this->assign('ratio',$ratio);
        $this->assign('allList',$all);
        $this->assign('directList',$direct);
        $this->assign('indirectList',$indirect);
        $this->assign('update',$update);
        $this->assign('charge',$charge);// 下级充值

        $this->assign('search',$search);
        $this->assign('type',$type);
        $this->assign('spider',['title'=>'收益记录','content'=>'','key'=>'']);
        return $this->fetch('profitLog');
    }

    /**
     * 我的收益-销售额转库存(done)
     */
    public function profitToStock()
    {
        $this->checkLogin();
        $user = session('user');
        $profit_total = model('Agents')->getAgentInfoByAid($user['a_id'],'profit');// 累计收益
        $this->assign('profit_total',$profit_total->profit);
        $this->assign('spider',['title'=>'销售额转库存','content'=>'','key'=>'']);
        return $this->fetch('profitToStock');
    }

    /**
     * 我的收益-销售额转库存-执行(done)
     */
    public function saveProfitToStock()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作',]];
        }
        $user = session('user');
        $profit_total = model('Agents')->getAgentInfoByAid($user['a_id'],'profit,stock_money');// 当前收益
        $received = request();
        $num = $received->param('num');
        if(isset($num) && $num <= $profit_total->profit)
        {
            $result = model('Agents')->execute('UPDATE `agents` SET stock_money=stock_money+'.$num.',profit=profit-'.$num.' WHERE agent_id='.$user['a_id']);// 转库存
            model('ProfitToStockLog')->insert(['a_id'=>$user['a_id'],'money'=>$num,'create_time'=>time(),'profit'=>$profit_total->profit,'stock'=>$num+$profit_total->stock_money]);// 添加记录
            if($result)
            {
                return ['msg'=>'操作成功'];
            }else{
                return ['error'=>['msg'=>'操作失败']];
            }
        }else{
            return ['error'=>['msg'=>'该转存金额无效']];
        }
    }

    /**
     * 我的收益-转库存记录(done)
     */
    public function toStockLog()
    {
        $this->checkLogin();
        $user = session('user');
        $list = model('ProfitToStockLog')->where(['a_id'=>$user['a_id'],'is_del'=>0])->order('create_time DESC')->select();
        $this->assign('list',$list);
        $this->assign('spider',['title'=>'转库存记录','content'=>'','key'=>'']);
        return $this->fetch('toStockLog');
    }

    /**
     * 我的库存(done)
     */
    public function myStock()
    {
        $this->checkLogin();
        $user = session('user');
        $now  = date('Y-m-d');// 当前时间
        $stock = model('Agents')->getAgentInfoByAid($user['a_id'],'stock_money');
        $stock_money = $stock->stock_money;// 当前库存
        $m_reward_agency = new AdminRewardAgency();
        $limit = $m_reward_agency->where(['role'=>$user['role']])->find();// 根据当前角色等级获取对应库存标准

        $max_stock=100;
        $min_stock=0;
        /* 计算百分比 S */
        isset($limit->pre_deposit) && $max_stock = $limit->pre_deposit;// 满值
        isset($limit->lowest_limit) && $min_stock = $limit->lowest_limit;// 警戒值

        // $middle_stock = 2 * $min_stock;// 安全值
        $middle_stock = $max_stock * 0.8;// 安全值 2018-04-27
        if($max_stock <= $stock_money)
        {
            $percent = 100;
            $color = '#45C01A';// 绿
        }else if($max_stock > $stock_money && $stock_money >= $middle_stock){
            $percent = round($stock_money/$max_stock,2)*100;
            $color = '#45C01A';// 绿
        }else if($middle_stock > $stock_money && $min_stock <= $stock_money){
            $percent = round($stock_money/$max_stock,2)*100;
            $color = '#FF8D30';// 橙
        }else if($stock_money < $min_stock){
            $percent = round($stock_money/$max_stock,2)*100;
            $color = '#DC1F1F';// 红
        }else{
            $percent = 0;
            $color = '#DC1F1F';// 红
        }
        /* 计算百分比 E */
        /* 出库 S */
        $sale_day = model('Agentstockchange')->where(['agent_id'=>$user['a_id'],'status'=>2,'change_type'=>3,'audit_time'=>['between',[strtotime($now),strtotime($now.' + 1 day')]]])->sum('money');// 减库存操作之和(日)
        $sale_all = model('Agentstockchange')->where(['agent_id'=>$user['a_id'],'status'=>2,'change_type'=>3])->sum('money');// 减库存操作之和(累计)
        /* 出库 E */
        /* 入库 S */
        $recharge_direct = model('Agentstockchange')->where(['agent_id'=>$user['a_id'],'status'=>2])->sum('money');// 直接充值
        $recharge_indirect = model('ProfitToStockLog')->where(['a_id'=>$user['a_id'],'is_del'=>0])->sum('money');// 收益转库存
        /* 入库 E */
        $data = [
            'stock'      => $stock_money,
            'percent'    => $percent,
            'color'      => $color,
            'day_sale'   => $sale_day,
            'all_sale'   => $sale_all,
            'all_income' => $recharge_indirect + $recharge_direct,
        ];
        
        //库存明细
        $change=model('Agentstockchange');

        $where = ['agent_id'=>$user['a_id'],'status'=>2,'change_type'=>['in','3,6,7,9,10,11']];
        $allList= $change->getStockChangeListAll($where);// 全部
        $this->assign('allList',$allList);
        
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'我的库存','content'=>'','key'=>'']);
        return $this->fetch('myStock');
    }

    /**
     * 我的库存-库存明细(done)
     */
    public function stockLog($search='',$type=0)
    {
        $this->checkLogin();
        $user = session('user');
        $reward=model('Agentorderreward');

        $where = ['sc.agent_id'=>$user['a_id'],'sc.status'=>2,'sc.change_type'=>3,'aor.reward_type'=>['in','4,3']];
        if(isset($search) && !empty($search))
        {
            $where['sc.order_number'] = ['like','%'.$search.'%'];
        }
        $direct_where = $redirect_where = $where;
        $direct_where['aor.reward_type']   = 4;
        $redirect_where['aor.reward_type'] = 3;
        //$allList      = model('Agentstockchange')->getStockChangeList($where);// 全部
        $allList=model('Agentstockchange')->getStockChangeListByType(array('change_type'=>['in','3,5,7'],'status'=>2,'agent_id'=>$user['a_id']));//充值库存扣减
        if(!empty($allList)) {
        	foreach($allList as $k=>$v) {
        		if($v['change_type']==3) {
        			$v['orderInfo']=$reward->where("order_number='".$v['order_number']."'")->field('sales_amount,reward_type')->find();
        		}
        	}
        }

        $directList   = model('Agentstockchange')->getStockChangeList($direct_where);// 直销
        $indirectList = model('Agentstockchange')->getStockChangeList($redirect_where);// 间接
        $updateList=model('Agentstockchange')->getStockChangeListByType(array('change_type'=>5,'status'=>2,'agent_id'=>$user['a_id']));//下级升级库存扣减
        $chargeList=model('Agentstockchange')->getStockChangeListByType(array('change_type'=>7,'status'=>2,'agent_id'=>$user['a_id']));//下级充值库存扣减

        $this->assign('allList',$allList);
        $this->assign('directList',$directList);
        $this->assign('indirectList',$indirectList);
        $this->assign('updateList',$updateList);
        $this->assign('chargeList',$chargeList);
        $this->assign('search',$search);
        $this->assign('type',$type);
        $this->assign('spider',['title'=>'库存明细','content'=>'','key'=>'']);
        return $this->fetch('stockLog');
    }
    
    /**
     * 我的库存-库存提货记录(done)
     */
    public function stockGoods($search='',$type=0)
    {
    	$this->checkLogin();
    	$user = session('user');
    	$reward=model('Agentorderreward');
    
    	$where = ['sc.agent_id'=>$user['a_id'],'sc.status'=>2,'sc.change_type'=>3,'aor.reward_type'=>['in','4,3']];
    	if(isset($search) && !empty($search))
    	{
    		$where['sc.order_number'] = ['like','%'.$search.'%'];
    	}
 
    	$allList      = model('Agentstockchange')->getStockChangeList($where);// 全部
    	$this->assign('allList',$allList);
     
    	$this->assign('search',$search);
    	$this->assign('type',$type);
    	$this->assign('spider',['title'=>'库存提货','content'=>'','key'=>'']);
    	return $this->fetch('stockGoods');
    }
    
    /**
     * 我的库存-库存转账记录(done)
     */
    public function stockTransfer()
    {
    	$this->checkLogin();
    	$user = session('user');
    	$transfer=model('Agentstocktransfer');
    
     	$where=array();
     	$where['operater_agent_id']=$user['a_id'];
    
    	$allList      = $transfer->getList($where);// 全部
    	$this->assign('allList',$allList);
 
    	$this->assign('spider',['title'=>'给下级代理转库存','content'=>'','key'=>'']);
    	return $this->fetch('stockTransfer');
    }
    
    /**
     * 我的库存-库存转账记录(done)
     */
    public function saveStockTransfer()
    {
    	$this->checkLogin();
    	$user = session('user');
    	$transfer=model('Agentstocktransfer');
    	$agent=model('Agents');
    
        $received  = request();
        $phone     = $received->param('phone');
        $money     = $received->param('money');
  

        $agentInfo = $agent->where(['phone'=>$phone,'is_del'=>0,'inviter'=>$user['a_id']])->find();// 被转账人信息
        $stockInfo = $agent->where(['agent_id'=>$user['a_id']])->find();//转账人信息

        if(!$agentInfo)
        {
            return ['error'=>['msg'=>'该用户不是您的直推下级，不能申报']];
        }else if($money > $stockInfo['stock_money']){
            return ['error'=>['msg'=>'您当前库存值不足，请先充值']];
        }else if($money > 9999999.9){
            return ['error'=>['msg'=>'转账金额输入错误，请重新输入']];
        }
 
   
        $result=agent_stock_transfer($user['a_id'],$agentInfo['agent_id'],$money);
        
        if(empty($result))
        {
            return ['error'=>['msg'=>'操作失败']];
        }else{
            return ['msg'=>'操作成功','url'=>url('Person/stockTransfer')];
        }
        
    }

    /**
     * 我的收入(done)
     */
    public function myIncome()
    {
        $this->checkLogin();
        $user = session('user');
        $profit = model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>['in','2,3,4,5,6,7,8']]);// 累计收益
        $m_reward_config = new AdminRewardConfig();
        $setting = $m_reward_config->find();// 系统设置
        $data = [];
        // 推荐奖励
        if (1 == $setting->valid_recommend_reward)
        {
            $data['invite'] = model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>3]);
        }
        // 代理收益+库存充值
        if(1 == $setting->valid_agency_reward)
        {
            $data['agent'] = model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>['in','2,6,7,8']]);
        }
        // 业绩分红
        if(1 == $setting->valid_performance_reward)
        {
            $data['reward'] = model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>4]);
        }
        //其他奖励 活动奖励
        $data['gift'] = model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>['in','5']]);
        // $this->assign('setting',$setting);
        $this->assign('income',$data);
        $this->assign('profit',$profit);
        $this->assign('spider',['title'=>'我的收入','content'=>'','key'=>'']);
        return $this->fetch('myIncome');
    }

    /**
     * 我的收入-招商奖励(done)
     */
    public function inviteReward($month = '')
    {
        $this->checkLogin();
        $user = session('user');
        $m_reward_config = new AdminRewardConfig();
        $system_setting = $m_reward_config->find();// 系统设置
        if(1 != $system_setting->valid_recommend_reward)
        {
            $this->redirect('myIncome');
        }
        $month        = empty($month) ? date('Y-m') : $month;// 查询月份
        $before_begin = strtotime($month.' - 1 month');// 上月
        $before_end   = strtotime($month.' - 1 month + '.(date('t',strtotime($month.' - 1 month'))).' day');// 上月
        $this_begin   = strtotime($month);// 本月
        $this_end     = strtotime($month.' + '.(date('t',strtotime($month))).' day');// 本月
        $data = [
            'total'  => model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>3]),// 累计总额
            'before' => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>3,'create_time'=>['between',[$before_begin,$before_end]]]),// 上月
            'now'    => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>3,'create_time'=>['between',[$this_begin,$this_end]]]),// 本月
        ];
        $list = model('Agentprofit')->getRewardLogsByDefined(['p.create_time'=>['between',[$this_begin,$this_end]],'p.agent_id'=>$user['a_id'],'p.type'=>3],'p.profit,p.type,o.agent_id,o.order_number,o.create_time');// 本月明细

        $stockChargeList=model('Agentprofit')->getStockChargeReward(['p.create_time'=>['between',[$this_begin,$this_end]],'p.agent_id'=>$user['a_id'],'p.type'=>3,'p.son_type'=>['in',['3','4']]]);//本月明细


        $this->assign('month',$month);
        $this->assign('data',$data);
        $this->assign('list',$list);
        $this->assign('stockChargeList',$stockChargeList);
        $this->assign('spider',['title'=>'招商奖励','content'=>'','key'=>'']);
        return $this->fetch('inviteReward');
    }

    /**
     * 我的收入-代理收益(done)
     */
    public function agentIncome($month = '')
    {
        $this->checkLogin();
        $user = session('user');
        $m_reward_config = new AdminRewardConfig();
        $system_setting = $m_reward_config->find();// 系统设置
        if(1 != $system_setting->valid_agency_reward)
        {
            $this->redirect('myIncome');
        }
        $month        = empty($month) ? date('Y-m') : $month;// 查询月份
        $before_begin = strtotime($month.' - 1 month');// 上月
        $before_end   = strtotime($month.' - 1 month + '.(date('t',strtotime($month.' - 1 month'))).' day');// 上月
        $this_begin   = strtotime($month);// 本月
        $this_end     = strtotime($month.' + '.(date('t',strtotime($month))).' day');// 本月
        $m_agent_profit = model('Agentprofit');
        $direct     = $m_agent_profit->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>6]);// 累计直接奖励
        $indirect   = $m_agent_profit->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>2]);// 累计间接
        $update   = $m_agent_profit->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>7]);// 累计下级升级奖励
        $charge   = $m_agent_profit->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>8]);// 累计下级充值奖励

        $b_direct   = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>6,'create_time'=>['between',[$before_begin,$before_end]]]);// 上月直接
        $b_indirect = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>2,'create_time'=>['between',[$before_begin,$before_end]]]);// 上月间接

        $b_update = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>7,'create_time'=>['between',[$before_begin,$before_end]]]);// 上月间接
        $b_charge = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>8,'create_time'=>['between',[$before_begin,$before_end]]]);// 上月间接


        $n_direct   = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>6,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月直接
        $n_indirect = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>2,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月间接
        $n_update = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>7,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月间接
        $n_charge = $m_agent_profit->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>8,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月间接

        $data['total']    = $direct + $indirect+$update+$charge;
        $data['before']   = $b_direct + $b_indirect+$b_update+$b_charge;// 上月
        $data['now']      = $n_direct + $n_indirect+$n_update+$n_charge;// 本月
        if($n_direct == 0 && $n_indirect == 0 && $n_update==0 && $n_charge==0)
        {
            $data['percent'] = [];
        }else if($n_direct == 0 || $n_indirect == 0){
            $data['percent'] = [
                ['item' => $n_direct == 0 ? '间接销售收益' : '直销收益','count' => 1]
            ];
        }else{
            $data['percent'] = [
                ['item' => '直销收益','count' => round($n_direct/$data['now'],2)],
                ['item' => '间接销售收益','count' => round($n_direct/$data['now'],2)],
               // ['item' => '下级升级收益','count' => round($n_update/$data['now'],2)],
                //['item' => '下级充值收益','count' => round($n_charge/$data['now'],2)],
            ];
        }

        $data['percent'] = json_encode($data['percent']);
        $list = $m_agent_profit->getRewardLogsByDefined(['p.create_time'=>['between',[$this_begin,$this_end]],'p.agent_id'=>$user['a_id'],'p.type'=>['in','6,2']],'p.profit,p.type,o.agent_id,o.order_number,o.create_time');// 本月明细

        $stockChargeList=$m_agent_profit->getStockChargeReward(['p.create_time'=>['between',[$this_begin,$this_end]],'p.agent_id'=>$user['a_id'],'p.type'=>['in','7,8']]);//本月明细
        $this->assign('data',$data);
        $this->assign('list',$list);
        $this->assign('stockChargeList',$stockChargeList);
        $this->assign('month',$month);
        $this->assign('spider',['title'=>'代理收益','content'=>'','key'=>'']);
        return $this->fetch('agentIncome');
    }

    /**
     * 我的收入-业绩分红(done)
     */
    public function achievementReward($month = '')
    {
        $this->checkLogin();
        $user = session('user');
        $m_reward_config = new AdminRewardConfig();
        $system_setting = $m_reward_config->find();// 系统设置
        if(1 != $system_setting->valid_performance_reward)
        {
            $this->redirect('myIncome');
        }
        $month      = empty($month) ? date('Y-m') : $month;// 查询月份
        $this_begin = strtotime($month);// 本月
        $this_end   = strtotime($month.' + '.(date('t',strtotime($month))).' day');// 本月
        $m_performance_reward = new AdminPerformanceReward();
        $data = [
            'total'     => model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>4]),// 累计总额
            'unsettled' => $m_performance_reward->getPerformanceByDefined(['agent_id'=>$user['a_id'],'status'=>1,'month'=>date('m',strtotime($month)),'year'=>date('Y',strtotime($month))]),// 本月待结算
            'settled'   => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>4,'create_time'=>['between',[$this_begin,$this_end]]]),// 本月已结算
        ];
        $list = $m_performance_reward->getPerformanceLogOnMonth(['agent_id'=>$user['a_id'],'status'=>['in','1,2'],'year'=>date('Y',strtotime($month))]);// 各月业绩分红
        $this->assign('month',$month);
        $this->assign('data',$data);
        $this->assign('list',$list);
        $this->assign('spider',['title'=>'业绩分红','content'=>'','key'=>'']);
        return $this->fetch('achievementReward');
    }

    /**
     * 我的收入-业绩分红-明细(done)
     */
    public function achievementRewardDetail($id)
    {
        $this->checkLogin();
        if(empty($id))
        {
            $this->redirect('achievementReward');
        }
        $m_reward_config = new AdminRewardConfig();
        $system_setting = $m_reward_config->find();// 系统设置
        if(1 != $system_setting->valid_performance_reward)
        {
            $this->redirect('myIncome');
        }
        $user = session('user');
        $m_reward_performance = new AdminRewardPerformance();
        $ratio = $m_reward_performance->getRatioByRole($user['role']);// 基础奖励系数
        $m_performance_reward = new AdminPerformanceReward();
        $info = $m_performance_reward->where(['id'=>$id,'agent_id'=>$user['a_id']])->find();// 业绩分红详情
        $time = $info->year.'-'.$info->month;// 业绩分红的年-月
        $this_begin = strtotime($time);
        $this_end   = strtotime($time.' + '.(date('t',strtotime($time))).' day');
        $invite = model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>3,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月推荐奖励
        $agent_direct = model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>1,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月直接
        $agent_indirect = model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>2,'create_time'=>['between',[$this_begin,$this_end]]]);// 本月间接
        $agent  = $agent_direct + $agent_indirect;// 本月代理收入
        $this->assign('info',$info);
        $this->assign('data',['invite'=>$invite,'agent'=>$agent,'ratio'=>$ratio]);
        $this->assign('spider',['title'=>'业绩分红','content'=>'','key'=>'']);
        return $this->fetch('achievementRewardDetail');
    }

    /**
     * 提现申请(done)
     */
    public function withdrawalsApp()
    {
        $this->checkLogin();
        $user = session('user');
        $profit_total = model('Agents')->getAgentInfoByAid($user['a_id'],'profit');// 累计收益
        $this->assign('profit',$profit_total->profit);
        $this->assign('spider',['title'=>'提现申请','content'=>'','key'=>'']);
        return $this->fetch('withdrawalsApp');
    }

    /**
     * 提现申请-申请提现(done)
     */
    public function appWithdrawals($type = 3)
    {
        $this->checkLogin();
        $user = session('user');
        $profit_total = model('Agents')->getAgentInfoByAid($user['a_id'],'profit');// 累计收益
        if($type != 3)
        {
        	$account = model('FinancialAccount')->where(['a_id'=>$user['a_id'],'type'=>$type])->order('id desc')->find();
        }else{
        	$account = [];
        }
        $this->assign('account',$account);
        $this->assign('type',$type);
        $this->assign('type_lang',[1=>'支付宝',2=>'银行卡',3=>'微信支付']);
        $this->assign('profit',$profit_total->profit);
        $this->assign('spider',['title'=>'提现申请','content'=>'','key'=>'']);
        return $this->fetch('appWithdrawals');
    }

    /**
     * 提现申请-申请提现-账户选择
     */
    public function financialChoice()
    {
        $this->checkLogin();
        $user = session('user');
        $type = [1=>'ali_set',2=>'bank_set',3=>'wechat_set'];
        $data = model('FinancialAccount')->getAgentFinancialState($user['a_id']);
        $wechat_is_bind = model('Weixinusers')->getUserOpenid($user['a_id']);// 微信是否绑定
        $data['wechat_set'] = $wechat_is_bind ? true : false;
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'账户选择','content'=>'','key'=>'']);
        return $this->fetch('financialChoice');
    }

    /**
     * 提现申请-申请提现-保存(done)
     */
    public function saveAppWithdrawals()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();
        $data['type'] = $received->param('type');
        $financial_account = model('FinancialAccount')->where(['a_id'=>$user['a_id'],'is_del'=>0,'type'=>$received->param('type')])->order('id desc')->find();// 选择的体现账户类型
        if($data['type'] != 3 && (!$financial_account || !$financial_account->account))
        {
            return ['error'=>['msg'=>'你的提现账户还未设置，请设置后再操作']];
        }
        $profit = model('Agents')->getAgentInfoByAid($user['a_id'],'profit');// 收益余额
        $data['a_id']         = $user['a_id'];
        $data['money']        = $received->param('num');
        $data['create_ctime'] = date('Y-m-d H:i:s');
        $data['remark']       = '申请提现'.$data['money'].'元';
        $data['change_before']= $profit->profit;
        if($data['money'] > $profit->profit)
        {
            return ['error'=>['msg'=>'可提现金额不足']];
        }
        if($data['type'] == 3)
        {
            $is_set_openId = model('Weixinusers')->getUserOpenid($user['a_id']);
            if(!$is_set_openId)
            {
                return ['error'=>['msg'=>'您的微信号未绑定，无法微信提现']];
            }
        }
        switch ($data['type'])
        {
            case '1':
                $data['account'] = $financial_account->account;
                break;
            case '2':
                $data['account'] = $financial_account->account;
                $data['account_bank'] = $financial_account->bank;
                $data['account_name'] = $financial_account->name;
                break;
            case '3':
                //$data['account'] = $financial_account->account;
                break;
            default:
                # code...
                break;
        }

		$result=model('Agents')->agentApplyWithdrawals($data);

        if ($result)
        {
            return ['msg'=>'提交成功'];
        } else {
            return ['error'=>['msg'=>'提交失败']];
        }
    }

    /**
     * 提现申请-提现记录(done)
     */
    public function withdrawalsLog()
    {
        $this->checkLogin();
        $user = session('user');
        $m_withdrawals_log = new AdminWithdrawalsLog();
        $list = $m_withdrawals_log->where(['a_id'=>$user['a_id']])->select();
        $this->assign('list',$list);
        $this->assign('spider',['title'=>'提现记录','content'=>'','key'=>'']);
        return $this->fetch('withdrawalsLog');
    }

    /**
     * 提现申请-订单详情(done)
     */
    public function withdrawalsDetail($id)
    {
        $this->checkLogin();
        $user = session('user');
        if(empty($id))
        {
            $this->redirect('withdrawalsLog');
        }
        $m_withdrawals_log = new AdminWithdrawalsLog();
        $audit_lang = [1=>'申请',2=>'支付',3=>'驳回'];
        $info = $m_withdrawals_log->where(['id'=>$id])->find();
        $this->assign('info',$info);
        $this->assign('audit_lang',$audit_lang);
        $this->assign('spider',['title'=>'订单详情','content'=>'','key'=>'']);
        return $this->fetch('withdrawalsDetail');
    }

    /**
     * 充值库存(done)
     */
    public function rechargeStock()
    {
        $this->checkLogin();
        $user = session('user');
        $stock = model('Agents')->getAgentInfoByAid($user['a_id'],'stock_money');
        $list = model('Agentstockchange')->where(['agent_id'=>$user['a_id'],'change_type'=>['in','1,2,8']])->order('audit_time DESC')->select();// 充值记录
        $this->assign('stock',$stock->stock_money);
        $this->assign('list',$list);
        $this->assign('spider',['title'=>'充值库存','content'=>'','key'=>'']);
        return $this->fetch('rechargeStock');
    }

    /**
     * 充值库存-充值(done)
     */
    public function recharge($type=0)
    {
        $this->checkLogin();
        $user = session('user');

        $type==0 && $type=3;//如果没有，那么默认是微信支付

        $account = model('FinancialAccount')->getAgentFinancialAccountArray($user['a_id'],$type);
        if($account)
        {
            $assign_account = $account;
        }else{
            switch ($type)
            {
                case 1:
                    $assign_account = ['name'=>'支付宝'];
                    break;
                case 2:
                    $assign_account = ['name'=>'银行卡'];
                    break;
                default:
                    $assign_account = ['name'=>'微信支付'];
                    break;
            }
        }
        // 2018-07-24 查询微信支付是否绑定
        $wechat_is_bind = model('Weixinusers')->getUserOpenid($user['a_id']);
        $this->assign('wechat_is_bind',$wechat_is_bind);
        
        //获取当前等级对应的充值最低库存
        $lowest_limit=get_lowest_limit_by_role($user['role']);
        $this->assign('lowest_limit',$lowest_limit);

        $this->assign('account',$assign_account);
        $this->assign('type',$type);//充值类型
        $this->assign('spider',['title'=>'充值','content'=>'','key'=>'']);
        return $this->fetch('recharge');
    }

    /**
     * 充值库存-充值-保存(done)
     */
    public function saveRecharge()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received = request();

        $type=$received->param('type');

        if($type==3) {//微信支付

        	//插入充值订单表，然后跳转到微信支付页面
        	$chargeorder=model('Agentstockchargeorder');
        	$data=array();
        	$order_number=$data['order_number']=$chargeorder->getOrderNumber($user['a_id']);
        	$data['create_time']=time();
        	$data['agent_id']=$user['a_id'];
        	$data['type']=1;//微信支付
        	$data['order_amount_pay']=$received->param('num');
        	$data['status']=1;//未支付
        	$res=$chargeorder->save($data);

        	//在获取一次OPENID
        	$openid='';
        	isset($user['openid']) && $openid=$user['openid'];
        	if(!isset($user['openid'])) {
        		$weixin=model('Weixinusers');
        		$openid=$weixin->getUserOpenid($user['a_id']);
        	}

        	$orderInfo=$chargeorder->field('order_amount_pay,type,status')->where("order_number='".$order_number."'")->find();

        	if(empty($openid)) {
        		return ['error'=>['msg'=>'openid获取失败，请绑定您的微信号']];
        	}

        	if($orderInfo['order_amount_pay']<=config('web.pay_limit') && $orderInfo['type']==1 && $openid) {
        		return $order_number;
        	} else {
        		return ['error'=>['msg'=>'提交失败,超过支付限额10000']];
        	}

        } else {

	        $stock = model('Agents')->getAgentInfoByAid($user['a_id'],'stock_money');
	        $data['agent_id']      = $user['a_id'];
	        $data['create_time']   = time();
	        $data['status']        = 1;
	        $data['change_before'] = $stock->stock_money;
	        $data['change_type']   = 1;
	        $data['money']         = $received->param('num');
	        $data['remark']        = $received->param('account','','trim');
            switch ($type)
            {
                case 1:
                    $data['account_type'] = 2;
                    break;
                case 2:
                    $data['account_type'] = 3;
                    break;
                default:
                    $data['account_type'] = 5;
                    break;
            }
	        $data['remark'] = '充值付款账号：'.$data['remark'];
	        $result = model('Agentstockchange')->insert($data);
	        if ($result)
	        {
	            return ['msg'=>'提交成功'];
	        } else {
	            return ['error'=>['msg'=>'提交失败']];
	        }
        }
    }

    /**
     * 活动奖励
     */
    public function promotionReward($month='')
    {
    	$this->checkLogin();
    	$user = session('user');
    	$profit=model('Agentprofit');

    	$month        = empty($month) ? date('Y-m') : $month;// 查询月份
    	$before_begin = strtotime($month.' - 1 month');// 上月
    	$before_end   = strtotime($month.' - 1 month + '.(date('t',strtotime($month.' - 1 month'))).' day');// 上月
    	$this_begin   = strtotime($month);// 本月
    	$this_end     = strtotime($month.' + '.(date('t',strtotime($month))).' day');// 本月

    	$data = [
    		'total'  => model('Agentprofit')->getTotalXRewardByAid(['a_id'=>$user['a_id'],'type'=>['in',[5]]]),// 累计总额
    		'before' => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>['in',[5]],'create_time'=>['between',[$before_begin,$before_end]]]),// 上月
    		'now'    => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id'],'type'=>['in',[5]],'create_time'=>['between',[$this_begin,$this_end]]]),// 本月
    	];

    	$list = model('Agentprofit')->getPromotionGiftReward(['p.create_time'=>['between',[$this_begin,$this_end]],'p.agent_id'=>$user['a_id'],'p.type'=>5],'p.profit,p.type,o.create_time,o.order_number,pgi.name');// 本月明细

    	$this->assign('month',$month);
    	$this->assign('data',$data);
    	$this->assign('list',$list);

        $this->assign('spider',['title'=>'其他奖励','content'=>'','key'=>'']);


        return $this->fetch('promotionReward');
    }

    //绑定微信
    public function bindWechatInfo()
    {
    	$openid=Session::get('openid');
    	$flag=0;
    	if(empty($openid)) {
    		//获取当前用户的ID，和openId
	    	$this->redirect('Index/Oauth/silentIndex');
	    	$openid=Session::get('openid');
	    	$flag=1;
    	}
    	$this->assign('openid',$openid);
    	$this->assign('flag',$flag);

    	return $this->fetch();
    }

    //保存
    public function saveBindWechatInfo()
    {
    	$res=0;

    	$request=request();
    	$user=session('user');
    	$openid=$request->param('openid');
    	//如果session openid为空然后才能保存
    	$weixin=model('Weixinusers');
    	//校验OPENID是否已经绑定
    	$info=$weixin->where("openid='".$openid."'")->find();
    	if(empty($info)) {
    		$data=array();
    		$data['openid']=$openid;
    		$data['agent_id']=$user['a_id'];
    		$data['create_time']=time();
    		$res=$weixin->save($data);
    	}

    	return $res;

    }
    
    
    /* 检查申报目标ID是否有效 */
    public function checkAgentIsSet($phone='')
    {
    	if(!session('?user'))
    	{
    		return ['error'=>['msg'=>'您还没有登录，请登录后操作']];
    	}
    	$m_agent_level = new AgentLevelModel();
    	$role_lang     = $m_agent_level->getRoleName(0);// 身份名称
    	if(empty($phone))
    	{
    		return ['error'=>['msg'=>'手机号码不能为空']];
    	}
    	if($phone == session('user.phone'))
    	{
    		return ['error'=>['msg'=>'不能填写自己的手机号，请重新填写']];
    	}
    	$a_id = model('agents')->field('name,agent_id,role')->where(['phone'=>$phone,'is_del'=>0])->find();// 被申报人
    	$sons = model('agents')->getAgentAllSonsId(['inviter'=>session('user.a_id'),'is_del'=>0]);
    	
    	if(empty($a_id)){
    		return ['error'=>['msg'=>'该手机号未注册，请先去注册']];
    	}else if(empty($sons)){
    		return ['error'=>['msg'=>'该用户不是您的直推下级，请重新申报']];
    	}
    	
    	return $a_id; 
    }
    
    //代他人充值库存
    public function saveChargeStockByWeChat()
    {
    	if(!session('?user'))
    	{
    		return ['error'=>['msg'=>'你还没有登录，请登录后操作']];
    	}
    	$user = session('user');
    	$received = request();
    	
    	$phone=$received->param('phone');
    	$money=$received->param('money');
    	
    	$agent=model('Agents');
    	$agentInfo=$agent->where("phone='".$phone."'")->find();
    	
    	//插入充值订单表，然后跳转到微信支付页面
    	$chargeorder=model('Agentstockchargeorder');
    	$data=array();
    	$order_number=$data['order_number']=$chargeorder->getOrderNumber($user['a_id']);
    	$data['create_time']=time();
    	$data['operator_agent_id']=$user['a_id'];
    	$data['agent_id']=$agentInfo['agent_id'];
    	$data['type']=1;//微信支付
    	$data['order_amount_pay']=$money;
    	$data['status']=1;//未支付
    	$res=$chargeorder->save($data);
    	
    	//在获取一次OPENID
    	$openid='';
    	isset($user['openid']) && $openid=$user['openid'];
    	if(!isset($user['openid'])) {
    		$weixin=model('Weixinusers');
    		$openid=$weixin->getUserOpenid($user['a_id']);
    	}
    	
    	$orderInfo=$chargeorder->field('order_amount_pay,type,status')->where("order_number='".$order_number."'")->find();
    	
    	if(empty($openid)) {
    		return ['error'=>['msg'=>'openid获取失败，请绑定您的微信号']];
    	}
    	
    	if($orderInfo['order_amount_pay']<=config('web.pay_limit') && $orderInfo['type']==1 && $openid) {
    		return $order_number;
    	} else {
    		return ['error'=>['msg'=>'提交失败,超过支付限额10000']];
    	}
    }
}