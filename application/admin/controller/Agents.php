<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\index\model\AgentAddress as IndexAddress;
use app\index\model\Agentorderreward as IndexOrderReward;
use app\index\model\Agents as IndexAgents;

class Agents extends Common
{
    /**
     *
     */
    public function index()
    {
        return '代理商管理-首页';
    }

    /**
     * 基础数据(角色数组+最高代数)(done)
     */
    public function basicData()
    {
        $role_arr = model('Agentlevel')->getRoleName();// 角色
        $last_g   = model('Agents')->getLastGeneration();// 最高代数
        return ['role_arr'=>$role_arr,'last_g'=>$last_g];
    }

    /**
     * 添加代理商(done)
     */
    public function addAgent()
    {
        $received   = request();
        $basic_data = $this->basicData();
        $m_agents   = model('Agents');
        $a_id       = $received->param('a_id');// 代理商ID
        if (!empty($a_id))
        {
            $agent_info = $m_agents->field('agent_id,nickname,wechat,phone,create_ctime,end_etime,generation,role,inviter,name,sex,id_card,province,city,area,address,is_del,`status`')->where('agent_id',$a_id)->find();// 代理商信息
            $examine_etime = model('AgentApplications')->getLastRegistePassExamineTime($a_id);
            $agent_info['create_ctime'] = $examine_etime ? $examine_etime : $agent_info['create_ctime'];// 若有通过的注册申请记录,显示审核时间
            if ($agent_info)
            {
                if($agent_info['province'])
                {
                    $cities = model('BasicDataAddress')->getSonCityList($agent_info['province']);
                    $this->assign('cities',$cities);
                }
                if($agent_info['city'])
                {
                    $areas = model('BasicDataAddress')->getSonCityList($agent_info['city']);
                    $this->assign('areas',$areas);
                }
                $financial_account = model('AgentFinancialAccount')->field('type,account,bank,name')->where(['a_id'=>$a_id,'is_del'=>0])->select();// 资金账户
                $accounts = [];
                if($financial_account)
                {
                    foreach ($financial_account as $key => $val)
                    {
                        $accounts[$val['type']]['account'] = $val['account'];
                        $accounts[$val['type']]['bank']    = $val['bank'];
                        $accounts[$val['type']]['name']    = $val['name'];
                    }
                }
                $this->assign('info',$agent_info);
                $this->assign('account',$accounts);
                $this->assign('wechat_is_bind',model('Weixinusers')->getIsBandByAgentId($a_id));
            } else {
                $this->redirect('agentsList');
            }
        }else{
            unset($basic_data['role_arr'][0]);// 录入时最低级从铜牌起,会员为自主注册最低
        }
        $all_agents = $m_agents->getAllAgents(['',empty($a_id)?'':$a_id]);// 可选邀请人
        $provinces = model('BasicDataAddress')->getProviceList();// 省级城市
        $this->assign('provinces',$provinces);
        $this->assign('basic',$basic_data);
        $this->assign('all_agents',$all_agents);
        return $this->fetch('addAgent');
    }

    /**
     * 获取城市级联列表数据(done)
     *
     * @param $pid 上一级城市ID
     */
    public function getAddress()
    {
        $id = Request::instance()->post('pid');
        if (!empty($id))
        {
            return $data = model('BasicDataAddress')->getSonCityList($id);
        } else {
            return array();
        }
    }

    /**
     * 添加代理商页面-检索符合条件的代理商邀请人(done)
     *
     * @param $search_inviter 检索邀请人的条件(姓名/微信号/代理商编号/手机号)
     */
    public function getAllInviterAgents()
    {
        $search_key = Request::instance()->post('key');
        $a_id       = Request::instance()->post('id');
        $m_agents   = model('Agents');
        $all_agents = $m_agents->getAllAgents([$search_key,empty($a_id) ? '' : $a_id]);
        return $all_agents;
    }

    /**
     * 保存代理商信息(添加/修改)(done)
     */
    public function saveAgentInfo(Request $received)
    {
        $a_id = $received->param('a_id');// 代理商ID
        $m_agents = model('Agents');
        $m_financial_account = model('AgentFinancialAccount');
        $password = $received->param('password','','trim');
        if(!$a_id)
        {// 添加
            $check_data = $this->validate($received->param(),'Agents');
            if (true !== $check_data)
            {
                $this->error($check_data);
            } else {
                $add_data = array(
                    'phone'        => $received->param('phone'),
                    'nickname'     => $received->param('nickname'),
                    'password'     => empty($password) ? md5(md5(substr($received->param('phone'), -6))) : md5(md5($password)),
                    'wechat'       => $received->param('wechat'),
                    'name'         => $received->param('name'),
                    'sex'          => $received->param('sex','m'),
                    'id_card'      => $received->param('id_card'),
                    'province'     => $received->param('province'),
                    'city'         => $received->param('city'),
                    'area'         => $received->param('area'),
                    'address'      => $received->param('address'),
                    'generation'   => 1,// 默认1代
                    'role'         => $received->param('role'),
                    'inviter'      => $received->param('inviter'),
                    'create_ctime' => date('Y-m-d H:i:s'),
                    'end_etime'    => -1,// 默认永久
                    'endTime'      => $received->param('end_time'),// 有效期
                    'status'       => 3,// 状态:已确认
                    'is_use'       => 1,
                    'family'       => '',
                );
                // 若存在邀请人,获取该邀请人的族谱
                if (!empty($add_data['inviter']))
                {
                    $up_agent = $m_agents->where(['agent_id'=>$add_data['inviter'],'is_del'=>0])->find();
                    $up_agent_family = $up_agent->family;
                    $add_data['generation'] = 1 + $up_agent->generation;// 变更代数
                    $add_data['family']     = empty($up_agent_family) ? $add_data['inviter'] : $up_agent_family.','.$add_data['inviter'];// 变更族谱
                }
                // 若代数为1,则无推荐人及上级族谱
                if($add_data['generation'] == 1)
                {
                    unset($add_data['inviter']);
                    unset($add_data['family']);
                }
                // 有效期
                if(!empty($add_data['endTime']))
                {
                    $add_data['end_etime'] = $add_data['endTime'];
                }
                unset($add_data['endTime']);// 删除
                $add_result = $m_agents->save($add_data);
                if ($add_result)
                {
                    $new_agent_id = $m_agents->agent_id;
                    // 同步地址信息到收货人列表并设置为默认
                    if($add_data['province'] || $add_data['city'] || $add_data['area'] || $add_data['address'])
                    {
                        $m_address = new IndexAddress();
                        $m_address->insert(['a_id'=>$new_agent_id,'name'=>$add_data['name'],'phone'=>$add_data['phone'],'province'=>$add_data['province'],'city'=>$add_data['city'],'area'=>$add_data['area'],'address'=>$add_data['address'],'is_default'=>1]);
                    }
                    // 添加金融账户信息
                    $financial_data = [];
                    if($received->param('ali_account'))
                    {// 支付宝
                        $financial_data[0]['a_id']         = $new_agent_id;
                        $financial_data[0]['create_ctime'] = $add_data['create_ctime'];
                        $financial_data[0]['type']         = 1;
                        $financial_data[0]['account']      = $received->param('ali_account');
                        $financial_data[0]['bank']         = null;
                        $financial_data[0]['name']         = null;
                        $financial_data[0]['is_default']   = 0;
                    }
                    if($received->param('wechat_account'))
                    {// 微信
                        $financial_data[1]['a_id']         = $new_agent_id;
                        $financial_data[1]['create_ctime'] = $add_data['create_ctime'];
                        $financial_data[1]['type']         = 3;
                        $financial_data[1]['account']      = $received->param('wechat_account');
                        $financial_data[1]['bank']         = null;
                        $financial_data[1]['name']         = null;
                        $financial_data[1]['is_default']   = 1;
                    }
                    if($received->param('bank_account') || $received->param('account_bank') || $received->param('account_name'))
                    {// 银行卡
                        $financial_data[2]['a_id']         = $new_agent_id;
                        $financial_data[2]['create_ctime'] = $add_data['create_ctime'];
                        $financial_data[2]['type']         = 2;
                        $financial_data[2]['account']      = $received->param('bank_account');
                        $financial_data[2]['bank']         = $received->param('account_bank');
                        $financial_data[2]['name']         = $received->param('account_name');
                        $financial_data[2]['is_default']   = 0;
                    }
                    $m_financial_account->saveAll($financial_data);// 银行账户添加
                    $new_application_data = ['a_id'=>$new_agent_id,'type'=>4,'create_ctime'=>$add_data['create_ctime'],'target'=>$add_data['role'],'remarks'=>'备注信息:公司录入','examine_etime'=>$add_data['create_ctime'],'status'=>1,'examiner'=>session('username')];
                    $this->saveApplication($new_application_data,1);// 添加申请记录
                    return ['msg'=>'添加成功'];
                } else {
                    return ['error'=>['msg'=>'添加失败']];
                }
            }
        }else{// 修改
            $info = $m_agents->field('phone,inviter,family,generation,role')->where(['agent_id'=>$a_id])->find();// 获取修改前数据
            $edit_data = array(
                'nickname' => $received->param('nickname'),
                'wechat'   => $received->param('wechat'),
                'name'     => $received->param('name'),
                'sex'      => $received->param('sex','m'),
                'id_card'  => $received->param('id_card'),
                'province' => $received->param('province'),
                'city'     => $received->param('city'),
                'area'     => $received->param('area'),
                'address'  => $received->param('address'),
                'role'     => $received->param('role'),
                'endTime'  => $received->param('end_time'),
            );
            $edit_data['role'] = isset($edit_data['role']) ? $edit_data['role'] : $info['role'];// 防止出现null

            //校验下，如果是降级，那么发个消息通知
            $this->degradationMessage($a_id,$edit_data['role']);

            //获取下当前的用户等级,插入等级变更记录
            $change=model('Agentchangerole');

            $data=array();
            $info['role']>$edit_data['role'] && $data['type']=1;
           	$info['role']<$edit_data['role'] && $data['type']=2;
            IF(isset($data['type'])) {
            	$data['create_time']=time();
            	$data['agent_id']=$a_id;
            	$data['before_role']=$info['role'];
            	$data['after_role']=$edit_data['role'];
            	$data['reason']=2;
            	$data['remark']="操作人:".Session::get('username');
            	$change->save($data);
            }

            /* 有效期 S */
            if(!empty($edit_data['endTime']))
            {
                $edit_data['end_etime'] = $edit_data['endTime'];
            }else{
                $edit_data['end_etime'] = -1;
            }
            unset($edit_data['endTime']);
            /* 有效期 E */
            /* 修改金融账户 S */
            $new_a_account = $received->param('ali_account');
            $new_b_account = $received->param('bank_account');
            $new_w_account = $received->param('wechat_account');
            $new_b_bank    = $received->param('account_bank');
            $new_b_name    = $received->param('account_name');
            $accounts      = $m_financial_account->field('id,type')->where(['a_id'=>$a_id,'is_del'=>0,'type'=>['in','1,2,3'],'account'=>['>',0]])->select();

            if($accounts)
            {
                $apear = [];// 已设置的账户类型的标记
                foreach ($accounts as $accounts_k => $accounts_v)
                {
                    if($accounts_v['type'] == 1)
                    {
                        $apear[] = 1;
                        $m_financial_account->save(['account'=>$new_a_account],['id'=>$accounts_v['id']]);
                    }
                    if($accounts_v['type'] == 2)
                    {
                        $apear[] = 2;
                        $m_financial_account->save(['account'=>$new_b_account,'bank'=>$new_b_bank,'name'=>$new_b_name],['id'=>$accounts_v['id']]);
                    }
                    if($accounts_v['type'] == 3)
                    {
                        $apear[] = 3;
                        $m_financial_account->save(['account'=>$new_w_account],['id'=>$accounts_v['id']]);
                        // echo $m_financial_account->getLastSql();
                    }
                }

                $unseted = array_diff([1,2,3],$apear);
                if(!empty($unseted))
                {
                    $new_financial_data = [];
                    foreach ($unseted as $unseted_v)
                    {
                        $new_financial_cache = [];
                        switch ($unseted_v)
                        {
                            case $unseted_v == '1' && !empty($new_a_account):
                                $new_financial_cache['a_id'] = $a_id;
                                $new_financial_cache['create_ctime'] = date('Y-m-d H:i:s');
                                $new_financial_cache['type'] = 1;
                                $new_financial_cache['account'] = $new_a_account;
                                break;
                            case $unseted_v == '2' && (!empty($new_b_account) || !empty($new_b_name) || !empty($new_b_bank)):
                                $new_financial_cache['a_id'] = $a_id;
                                $new_financial_cache['create_ctime'] = date('Y-m-d H:i:s');
                                $new_financial_cache['type'] = 2;
                                $new_financial_cache['account'] = $new_b_account;
                                $new_financial_cache['bank'] = $new_b_bank;
                                $new_financial_cache['name'] = $new_b_name;
                                break;
                            case $unseted_v == '3' && !empty($new_w_account):
                                $new_financial_cache['a_id'] = $a_id;
                                $new_financial_cache['create_ctime'] = date('Y-m-d H:i:s');
                                $new_financial_cache['type'] = 3;
                                $new_financial_cache['account'] = $new_w_account;
                                break;
                            default:
                                # code...
                                break;
                        }
                        $new_financial_data[] = $new_financial_cache;
                    }
                    $m_financial_account->insertAll($new_financial_data);
                }
            }else{
                $financial_data = [
                    [
                        'a_id' => $a_id,
                        'create_ctime' => date('Y-m-d H:i:s'),
                        'type' => 1,
                        'account' => $new_a_account,
                        'bank' => null,
                        'name' => null,
                        'is_default' => 0,
                    ],[
                        'a_id' => $a_id,
                        'create_ctime' => date('Y-m-d H:i:s'),
                        'type' => 2,
                        'account' => $new_b_account,
                        'bank' => $new_b_bank,
                        'name' => $new_b_name,
                        'is_default' => 0,
                    ],[
                        'a_id' => $a_id,
                        'create_ctime' => date('Y-m-d H:i:s'),
                        'type' => 3,
                        'account' => $new_w_account,
                        'bank' => null,
                        'name' => null,
                        'is_default' => 1,
                    ]
                ];
                $m_financial_account->insertAll($financial_data);
            }
            /* 修改金融账户 E */
            /* 对比手机号是否更改 S */
            if($received->param('phone') != $info->phone)
            {
                $isset = $m_agents->where(['phone'=>$received->param('phone'),'is_del'=>0])->count();
                if($isset)
                {
                    return ['error'=>['msg'=>'该手机号已被注册使用']];
                }
                $edit_data['phone']    = $received->param('phone');
                $edit_data['password'] = empty($password) ? md5(md5(substr($edit_data['phone'], -6))) : md5(md5($password));
            }else{
                // 未更改手机号的情况下,如果password为空则密码不更改
                if(!empty($password))
                {
                    $edit_data['password'] = md5(md5($password));
                }
            }
            /* 对比手机号是否更改 E */
            /* 对比邀请人字段是否更改 S */
            if ($received->param('inviter') != $info->inviter)
            {
                $edit_data['inviter'] = $received->param('inviter');
                // 非空时查询该邀请人的族谱
                if(!empty($edit_data['inviter']))
                {
                    $edit_data['family']     = $m_agents->getNewFamily([$edit_data['inviter']]);
                    $edit_data['generation'] = count(explode(',', $edit_data['family'])) + 1;// 代数
                }else{
                    $edit_data['family']     = '';
                    $edit_data['generation'] = 1;// 代数
                }
                // 代数是否变更
                $g_change_num = 0;// 代数变更量
                if($edit_data['generation'] != $info->generation)
                {
                    $g_change_num = $edit_data['generation'] - $info->generation;
                }
                // 2018-07-11 CYL 添加邀请人变更记录
                $change_relation_data['a_id']     = $a_id;
                $change_relation_data['examiner'] = session('username');
                $change_relation_data['remarks']  = '邀请人变更';
                $change_relation_data['now']      = $info->inviter;// 当前上级
                $change_relation_data['now']      = isset($change_relation_data['now']) ? $change_relation_data['now'] : '-1';
                $change_relation_data['target']   = $edit_data['inviter'];// 变后上级
                $change_relation_data['now_g']    = $info->generation;// 当前代数
                $change_relation_data['target_g'] = $edit_data['generation'];// 变后代数
                model('AgentApplications')->recordChangeRelationLog($change_relation_data);
            }
            /* 对比邀请人字段是否更改 E */
            $edit_result = $m_agents->save($edit_data,['agent_id'=>$a_id]);// 修改
            // 修改成功并且修改了邀请人信息,更新所有下级会员的族谱
            if($edit_result && $received->param('inviter') != $info->inviter)
            {
                $son_list = $m_agents->getAllSonAgentList([$a_id]);
                if(count($son_list) > 0)
                {
                    foreach ($son_list as $son_list_k => $son_list_v)
                    {
                        $son_list_family = trim($edit_data['family'].','.substr($son_list_v['family'], strpos($son_list_v['family'], $a_id)),',');
                        $m_agents->save(['family'=>$son_list_family,'generation'=>$son_list_v['generation']+$g_change_num],['agent_id'=>$son_list_v['a_id']]);

                        // 2018-07-11 CYL 添加邀请人变更记录
                        $change_relation_data = [];
                        $change_relation_data['a_id']     = $son_list_v['a_id'];
                        $change_relation_data['examiner'] = session('username');
                        $change_relation_data['remarks']  = '上级的邀请人变更,本级仅关联变更代数和族谱';
                        $change_relation_data['now']      = $son_list_v['inviter'];// 当前上级
                        $change_relation_data['now']      = isset($change_relation_data['now']) ? $change_relation_data['now'] : '-1';
                        $change_relation_data['target']   = $change_relation_data['now'];// 变后上级
                        $change_relation_data['now_g']    = $son_list_v['generation'];// 当前代数
                        $change_relation_data['target_g'] = $son_list_v['generation']+$g_change_num;// 变后代数
                        model('AgentApplications')->recordChangeRelationLog($change_relation_data);
                    }
                }
            }
            return ['msg'=>'保存成功'];
        }
    }

    /**
     * 添加代理商申请(done)
     *
     * @param $application array 准备好的数据(a_id,type,create_ctime,target,remarks)
     * @param $return_type int 要求返回的数据类型 0正常请求 1布尔(暂定)
     *
     * $add_data = [
     *      'a_id'         => agent_id,
     *      'type'         => 1|2|3,
     *      'create_ctime' => '2018-1-18 10:15:55',
     *      'target'       => 1|(1|2|...|n)|agent_id,
     *      'remarks'      => '备注信息: ****',
     *  ];
     *
     */
    public function saveApplication($application,$return_type=0)
    {
        if ($application)
        {
            $add_data = $application;
        } else {
            $received = Request::instance()->param();
            $create_ctime = $received->param('create_ctime');
            $add_data = [
                'a_id' => $received->param('a_id'),
                'type' => $received->param('type'),
                'create_ctime' => !empty($create_ctime) ? $create_ctime : date('Y-m-d H:i:s'),
                'target' => $received->param('target'),
                'remarks' => $received->param('remarks'),
            ];
        }
        $m_application = model('AgentApplications');
        $add_result = $m_application->save($add_data);
        if ($add_result)
        {
            if ($return_type)
            {
                return true;
            } else {
                return '申请成功';
            }
        } else {
            if ($return_type)
            {
                return false;
            } else {
                return '提交失败';
            }
        }
    }

    /**
     * 代理商管理-全部列表(done)
     */
    public function agentsList()
    {
        $m_agents      = model('Agents');
        $m_agent_level = model('Agentlevel');
        $param = request();

        $levelList=$m_agent_level->gerRoleList();//所有角色列表

        $type_lang   = ['','申请注册','申请升级','已确认','驳　回','取　消'];
        // 检索条件 S
        $where = ['a.is_del'=>0];
        $search_status = $param->param('status');// 申请状态
        $search_status = !isset($search_status) ? -1 : $search_status;
        switch ($search_status)
        {
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
                $where['a.status'] = $search_status;
                break;
            default:
                $where['a.status'] = ['in','0,1,2,3,4,5,6'];
                break;
        }
        $search_a_id = $param->param('a_id','','trim');
        if($search_a_id)
        {
            $where['a.agent_id'] = $search_a_id;
        }
        $search_name = $param->param('name','','trim');
        if($search_name)
        {
            $where['a.name'] = ['like','%'.$search_name.'%'];
        }
        $search_phone = $param->param('phone','','trim');
        if($search_phone)
        {
            $where['a.phone'] = ['like','%'.$search_phone.'%'];
        }
        $search_generation = $param->param('generation');
        if($search_generation)
        {
            $where['a.generation'] = $search_generation;
        }
        $search_role = $param->param('role');
        $search_role = !isset($search_role) ? -1 : $search_role;
        if($search_role >= 0)
        {
            $where['a.role'] = $search_role;
        }
        $search_begin = $param->param('begin');
        $search_end = $param->param('end');
        if($search_begin && $search_end){
            $where['a.create_ctime'] = ['between',[$search_begin,date('Y-m-d',strtotime($search_end.' + 1 day'))]];
        }else{
            if($search_begin)
            {
                $where['a.create_ctime'] = ['>=',$search_begin];
            }
            if($search_end)
            {
                $where['a.create_ctime'] = ['<=',date('Y-m-d',strtotime($search_end.' + 1 day'))];
            }
        }
        // 检索条件 E
        $app_list = $m_agents->alias('a')->field('a.agent_id as a_id,a.name,a.phone,a.wechat,a.generation,a.role,a.inviter,a.family,a.stock_money,a.create_ctime,a.status,r.lowest_limit')->join('agent_reward_agency r','a.role=r.role','LEFT')->where($where)->order('a.create_ctime desc')->paginate(config('paginate.list_rows'),false,['query'=>$param->param()]);
        $m_order_reward = new IndexOrderReward();
        $m_index_agents = new IndexAgents();
        $weixin=model('admin/Weixinusers');
        // 获取团队人数(会员角色等级以下并且在族内的所有代理商)/销售总额
        foreach ($app_list as $app_k => $app_v)
        {
        	$app_v['bind_wechat']=$weixin->getIsBandByAgentId($app_v['a_id']);
            $app_v['team_num']  = count($m_index_agents->getSons($app_v['a_id'],$app_v['role'],2));
            $app_v['type_lang'] = $type_lang[$app_v['status']];
            $app_v['sale_num']  = count_team_orders_sales_total([$app_v['a_id']]);// $m_order_reward->getSaleTotalByAgentId($app_v['a_id']);// 销售总额

            $app_v['total'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>['in','2,3,4,5,6,7,8']]);// 累计收益
            $app_v['invite'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>3]);// 招商收益
            $app_v['reward'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>4]);// 业绩分红
            $app_v['direct'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>6]);// 直接代理收入
            $app_v['indirect'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>2]);// 间接代理收入
            $app_v['other'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>['in','5']]);// 其他收入
            $app_v['other_gift'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>5]);// 礼包收益
            $app_v['lower'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>7]);// 下级升级奖励
            $app_v['charge'] = model('index/Agentprofit')->getTotalXRewardByAid(['a_id'=>$app_v['a_id'],'type'=>8]);// 库存充值奖励
        }
        $this->assign('list',$app_list);
        $this->assign('levelList',$levelList);
        $this->assign('search',$param->param());
        $this->assign('last_g',$m_agents->getLastGeneration());// 最高代数

        return $this->fetch('agentsList');
    }

    /**
     * 删除代理商(done)
     */
    public function delAgent()
    {
        $received = request();
        $a_id = $received->param('id');
        if(!empty($a_id))
        {
            $m_agents = model('Agents');
            $del_result = $m_agents->save(['is_del'=>1],['agent_id'=>$a_id]);
            if($del_result)
            {
                return ['msg'=>'删除成功'];
            }else{
                return ['error'=>['msg'=>'删除失败']];
            }
        }else{
            return ['error'=>['msg'=>'参数错误']];
        }
    }

    /**
     * 代理商申请列表-重置密码(done)
     */
    public function resetAgentPassword()
    {
        $received = request();
        $this->resetPassword($received->param('a_id'),1);
    }

    /**
     * 重置密码(done)
     *
     * @param $a_id int 代理商ID
     * @param $type int 重置类型: 0随机字符串 1手机号后六位
     */
    protected function resetPassword($a_id,$type=1)
    {
        if ($a_id)
        {
            $m_agents = model('Agents');
            switch ($type)
            {
                case '1':
                    $phone = $m_agents->where(['agent_id'=>$a_id])->column('phone');
                    $new_pass = md5(md5(trim(substr($phone[0], -6))));
                    break;
                case '0':
                default:
                    $new_pass = md5(md5(substr(mt_rand(0.000001,0.999999),-6)));
                    break;
            }
            $result = $m_agents->save(['password'=>$new_pass],['agent_id'=>$a_id]);
            if ($result)
            {
                return true;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 代理商申请管理-待审核列表(done)
     */
    public function applyListAgentAudit()
    {
        $m_application = model('AgentApplications');
        $m_agents      = model('Agents');
        $received = request();
        $m_agent_level = model('Agentlevel');

        $type_lang = [1=>'代理商申请',2=>'升级申请',3=>'变更申请',4=>'公司录入',5=>'礼包升级',6=>'下级申报'];
        // 检索条件 S
        $where = ['p.is_del'=>0,'p.status'=>0];
        $search_a_id = $received->param('a_id');
        if($search_a_id)
        {
            $where['p.a_id'] = $search_a_id;
        }
        $search_name = $received->param('name');
        if($search_name)
        {
            $where['a.name'] = ['like','%'.$search_name.'%'];
        }
        $search_generation = $received->param('generation');
        if($search_generation)
        {
            $where['a.generation'] = $search_generation;
        }
        $search_phone = $received->param('phone','','trim');
        if($search_phone)
        {
        	$where['a.phone'] = ['like','%'.$search_phone.'%'];
        }
        $search_role = $received->param('role');
        if(isset($search_role) && $search_role >= 0)
        {
            $where['a.role'] = $search_role;
        }
        $search_begin = $received->param('begin');
        $search_end = $received->param('end');
        if($search_begin && $search_end){
            $where['p.create_ctime'] = ['between',[$search_begin,$search_end]];
        }else{
            if($search_begin)
            {
                $where['p.create_ctime'] = ['>=',$search_begin];
            }
            if($search_end)
            {
                $where['p.create_ctime'] = ['<=',$search_end];
            }
        }
        // 检索条件 E
        $app_list = $m_application->alias('p')->field('p.id,p.a_id,p.create_ctime,p.type,p.target,p.remarks,p.money,p.img,p.apply_by_id,a.name,a.phone,a.wechat,a.generation,a.role,a.stock_money,a2.name AS apply_name,a2.phone AS apply_phone')->join('agents a','p.a_id=a.agent_id AND a.is_del=0')->join('agents a2','p.apply_by_id=a2.agent_id AND a2.is_del=0','LEFT')->where($where)->order('p.create_ctime desc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
        $m_order_reward = new IndexOrderReward();
        // 获取团队人数(会员角色等级以下并且在族内的所有代理商)/销售总额
        foreach ($app_list as $app_k => $app_v)
        {
            $app_v['team_num']  = $m_agents->where(['role'=>['<',$app_v['role']],'family'=>['like','%'.$app_v['a_id'].'%'],'is_del'=>0])->count();
            $app_v['type_lang'] = $type_lang[$app_v['type']];
            $app_v['sale_num']  = count_team_orders_sales_total([$app_v['a_id']]);// 销售总额
        }

        $levelList=$m_agent_level->gerRoleList();//所有角色列表
        $this->assign('levelList',$levelList);

        $this->assign('list',$app_list);
        $this->assign('search',$received->param());
        $this->assign('last_g',$m_agents->getLastGeneration());// 最高代数
        return $this->fetch('applyListAgentAudit');
    }

    /**
     * 代理商申请管理-已确认列表(done)
     */
    public function applyListAgentAgree()
    {
        $m_application = model('AgentApplications');
        $m_agents      = model('Agents');
        $received = request();
        $m_agent_level = model('Agentlevel');

        $levelList=$m_agent_level->gerRoleList();//等级列表

        // 检索条件 S
        $where = ['p.is_del'=>0,'p.status'=>1];
        $search_a_id = $received->param('a_id');
        if($search_a_id)
        {
            $where['p.a_id'] = $search_a_id;
        }
        $search_name = $received->param('name');
        if($search_name)
        {
            $where['a.name'] = ['like','%'.$search_name.'%'];
        }
        $search_generation = $received->param('generation');
        if($search_generation)
        {
            $where['a.generation'] = $search_generation;
        }
        $search_phone = $received->param('phone','','trim');
        if($search_phone)
        {
        	$where['a.phone'] = ['like','%'.$search_phone.'%'];
        }
        $search_role = $received->param('role');
        if(isset($search_role) && $search_role >= 0)
        {
            $where['a.role'] = $search_role;
        }
        $search_begin = $received->param('begin');
        $search_end = $received->param('end');
        if($search_begin && $search_end){
            $where['p.create_ctime'] = ['between',[$search_begin,$search_end]];
        }else{
            if($search_begin)
            {
                $where['p.create_ctime'] = ['>=',$search_begin];
            }
            if($search_end)
            {
                $where['p.create_ctime'] = ['<=',$search_end];
            }
        }
        // 检索条件 E
        $app_list = $m_application->alias('p')->field('p.id,p.a_id,p.create_ctime,p.type,p.examine_etime,p.remarks,p.money,p.img,p.apply_by_id,a.name,a.phone,a.wechat,a.generation,a.role,a.stock_money,a2.name AS apply_name,a2.phone AS apply_phone')->join('agents a','p.a_id=a.agent_id AND a.is_del=0')->join('agents a2','p.apply_by_id=a2.agent_id AND a2.is_del=0','LEFT')->where($where)->order('p.examine_etime desc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
        $m_order_reward = new IndexOrderReward();
        // 获取团队人数(会员角色等级以下并且在族内的所有代理商)/销售总额
        foreach ($app_list as $app_k => $app_v)
        {
            $app_v['team_num']  = $m_agents->where(['role'=>['<',$app_v['role']],'family'=>['like','%'.$app_v['a_id'].'%'],])->count();

            $app_v['sale_num']  = count_team_orders_sales_total([$app_v['a_id']]);// $m_order_reward->getSaleTotalByAgentId($app_v['a_id']);/* 销售总额 */
        }
        $this->assign('list',$app_list);
        $this->assign('levelList',$levelList);
        $this->assign('search',$received->param());
        $this->assign('last_g',$m_agents->getLastGeneration());// 最高代数
        return $this->fetch('applyListAgentAgree');
    }

    /**
     * 代理商申请管理-驳回列表(done)
     */
    public function applyListAgentDisagree()
    {
        $m_application = model('AgentApplications');
        $m_agents      = model('Agents');
        $received = request();
        $m_agent_level = model('Agentlevel');
        $levelList = $m_agent_level->gerRoleList();// 角色定义的名称数组

        // 检索条件 S
        $where = ['p.is_del'=>0,'p.status'=>2];
        $search_a_id = $received->param('a_id');
        if($search_a_id)
        {
            $where['p.a_id'] = $search_a_id;
        }
        $search_name = $received->param('name');
        if($search_name)
        {
            $where['a.name'] = ['like','%'.$search_name.'%'];
        }
        $search_generation = $received->param('generation');
        if($search_generation)
        {
            $where['a.generation'] = $search_generation;
        }
        $search_phone = $received->param('phone','','trim');
        if($search_phone)
        {
        	$where['a.phone'] = ['like','%'.$search_phone.'%'];
        }
        $search_role = $received->param('role');
        if(isset($search_role) && $search_role >= 0)
        {
            $where['a.role'] = $search_role;
        }
        $search_begin = $received->param('begin');
        $search_end = $received->param('end');
        if($search_begin && $search_end){
            $where['p.create_ctime'] = ['between',[$search_begin,$search_end]];
        }else{
            if($search_begin)
            {
                $where['p.create_ctime'] = ['>=',$search_begin];
            }
            if($search_end)
            {
                $where['p.create_ctime'] = ['<=',$search_end];
            }
        }
        // 检索条件 E
        $app_list = $m_application->alias('p')->field('p.id,p.a_id,p.create_ctime,p.type,p.examine_etime,p.remarks,p.money,p.img,p.apply_by_id,a.name,a.phone,a.wechat,a.generation,a.role,a.stock_money,a2.name AS apply_name,a2.phone AS apply_phone')->join('agents a','p.a_id=a.agent_id AND a.is_del=0')->join('agents a2','p.apply_by_id=a2.agent_id AND a2.is_del=0','LEFT')->where($where)->order('p.examine_etime desc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
        $m_order_reward = new IndexOrderReward();
        // 获取团队人数(会员角色等级以下并且在族内的所有代理商)/销售总额
        foreach ($app_list as $app_k => $app_v)
        {
            $app_v['team_num']  = $m_agents->where(['role'=>['<',$app_v['role']],'family'=>['like','%'.$app_v['a_id'].'%'],])->count();
            $app_v['sale_num']  = count_team_orders_sales_total([$app_v['a_id']]);// $m_order_reward->getSaleTotalByAgentId($app_v['a_id']);/* 销售总额 */
        }
        $this->assign('list',$app_list);
        $this->assign('levelList',$levelList);
        $this->assign('search',$received->param());
        $this->assign('last_g',$m_agents->getLastGeneration());// 最高代数
        return $this->fetch('applyListAgentDisagree');
    }

    /**
     * 审核代理商申请-通过/驳回/删除(done)
     */
    public function examineApply()
    {
        $received = request();
        $apply_id = $received->param('id');// 申请ID
        $type     = $received->param('type');// 修改类型
        if($apply_id && $type)
        {
            $apply_data = [];// 申请表修改数据
            $where      = [];// 查询申请信息条件
            switch ($type)
            {
                case 1:// 通过
                    $apply_data['status'] = 1;
                    $apply_data['examine_etime'] = date('Y-m-d H:i:s');
                    $apply_data['examiner'] = session('username');// 操作人
                    $where['is_del'] = 0;
                    $where['status'] = 0;
                    $msg = '该条申请已处理或不存在';
                    break;
                case 2:// 驳回
                    $apply_data['examine_remark'] = $received->param('remark');
                    $apply_data['status'] = 2;
                    $apply_data['examine_etime'] = date('Y-m-d H:i:s');
                    $apply_data['examiner'] = session('username');// 操作人
                    $where['is_del'] = 0;
                    $where['status'] = 0;
                    $msg = '该条申请已处理或不存在';
                    break;
                case 3:// 删除
                    $apply_data['is_del'] = 1;
                    $where['is_del'] = 0;
                    $where['status'] = ['<>',0];// 未审核过的申请不允许直接删除
                    $msg = '该条申请还未审核，不能删除';
                    break;
                default:
                    return ['error'=>['msg'=>'参数错误']];
                    break;
            }
            $where['id'] = $apply_id;
            $m_application = model('AgentApplications');
            $apply_info    = $m_application->field('type,target,a_id,id')->where($where)->find();
            if(!$apply_info)
            {
                return ['error'=>['msg'=>$msg]];
            }else{
                if($type == 1)
                {// 通过
                    $m_agents = model('Agents');
                    $edit_agents_data = [];// 代理商表修改数据
                    // 申请类型
                    switch ($apply_info['type'])
                    {
                        case 1:// 注册申请
                            $edit_agents_data['role'] = $apply_info['target'];
                            $edit_agents_data['status'] = 3;// 已确认
                            $edit_agents_data['is_use'] = 1;
                            break;
                        case 2:// 升级申请
                            $edit_agents_data['role'] = $apply_info['target'];
                            $edit_agents_data['status'] = 3;// 已确认
                            break;
                        case 3:// 变更申请
                            $edit_agents_data['inviter'] = $apply_info['target'];// 上下级限制在该申请提交处处理
                            $edit_agents_data['family'] = $m_agents->getNewFamily([$apply_info['target']]);
                            $edit_agents_data['status'] = 3;// 已确认
                            break;
                        case 6:// 上级申报
                            $edit_agents_data['role'] = $apply_info['target'];
                            $edit_agents_data['status'] = 3;// 已确认
                            break;
                        default:
                            return ['error'=>['msg'=>'数据错误','code'=>'c:agents:'.__LINE__]];
                            break;
                    }


                    //上级申报-下单升级
                    if(in_array($apply_info['type'],array(6))) {
                    	//下级升级
                    	lower_agent_direct_raise_by_upper($apply_info['id']);
                    }

                    $agentInfo=model('Agents')->field('phone')->find($apply_info['a_id']);
                    //申请通过 发个消息给用户
                    $mdata=array();
                    $mdata['first']='您的申请已通过！';
                    $mdata['account']=$agentInfo['phone'];
                    $mdata['time']=date('Y-m-d H:i:s');
                    $mdata['type']='审核通过';
                    $mdata['remark']='你的申请已经通过，请重新登录获取新的身份';

                    //给下单人发送发货通知
                    message_notification(3,$apply_info['a_id'],$mdata);

                    $edit_apply_result = $m_application->save($apply_data,['id'=>$apply_id]);
                    if($edit_apply_result)
                    {
                        $edit_agents_result = $m_agents->save($edit_agents_data,['agent_id'=>$apply_info['a_id']]);// 修改该代理商信息
                        if($apply_info['type'] == 3)
                        {
                            // 修改该代理商所有子级的族谱
                            $m_agents->loopModifySonsFamily([$apply_info['a_id']]);
                        }

                        //找到是升级申请,那么系统自动给下个单
                        $apply_info['type']==1 && agent_auto_order($apply_info['a_id']);

                        return ['msg'=>'操作成功'];
                    }else{
                        return ['error'=>['msg'=>'操作失败','code'=>$type]];
                    }
                }else{// 驳回和删除
                    $edit_result = $m_application->save($apply_data,['id'=>$apply_id]);
                    if ($edit_result === false)
                    {
                        return ['error'=>['msg'=>'操作失败','code'=>$type]];
                    } else {
                        model('Agents')->save(['status'=>4],['agent_id'=>$apply_info['a_id']]);// 修改身份状态
                        return ['msg'=>'操作成功'];
                    }
                }
            }
        }else{
            return ['error'=>['msg'=>'参数错误']];
        }
    }

    /**
     * 直属代理(所有角色等级低于该代理商的族谱下线)(done)
     */
    public function underAgent()
    {
        $received = request();
        $a_id = $received->param('id');// 编号
        $r_id = $received->param('rid');// 角色
        if (!empty($a_id) && $r_id >= 0)
        {
            $m_agents = model('Agents');
            $m_application = model('AgentApplications');
            $m_agent_level = model('Agentlevel');
            $levelList = $m_agent_level->gerRoleList();// 角色定义的名称数组
            $m_index_agents = new IndexAgents();
            $son_id = $m_index_agents->getSons($a_id,$r_id,4);// 获取所有子级代理(包含会员)
            $agent_list = [];
            if(!empty($son_id))
            {
                if($received->param('s_a_id'))
                {
                    $where['agent_id'] = [['in',implode(',',$son_id)],['eq',$received->param('s_a_id')]];
                }else{
                    $where = [
                        'agent_id'=>['in',implode(',', $son_id)]
                    ];
                }
                if($received->param('name'))
                {
                    $where['name'] = ['like','%'.$received->param('name','','trim').'%'];
                }
                if($received->param('wechat'))
                {
                    $where['wechat'] = ['like','%'.$received->param('wechat','','trim').'%'];
                }
                if($received->param('phone'))
                {
                    $where['phone'] = $received->param('phone','','trim');
                }
                if($received->param('generation'))
                {
                    $where['generation'] = $received->param('generation','','trim');
                }
                $search_role = $received->param('role','','trim');
                switch ($search_role)
                {
                    case '0':
                    case '1':
                    case '2':
                    case '3':
                    case '4':
                    case '5':
                    case '6':
                    case '7':
                        $where['role'] = ['eq',$search_role];
                        break;
                    default:

                        break;
                }
                $agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                if($agent_list)
                {
                    $m_order_reward = new IndexOrderReward();
                    foreach ($agent_list as $a_list_k => $a_list_v)
                    {
                        $a_list_v['sales_money'] = count_team_orders_sales_total([$a_list_v['agent_id']]);// $m_order_reward->getSaleTotalByAgentId($a_list_v['agent_id']);/* 销售总额 */
                        $a_list_v['under_count'] = count($m_index_agents->getSons($a_list_v['agent_id'],$a_list_v['role'],2));// 直属代理数
                        $a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
                        // 获取已审核变更邀请人记录的时间
                        $change_time = $m_application->where(['status'=>1,'target'=>$a_id,'type'=>3,'a_id'=>$a_list_v['agent_id']])->order('examine_etime DESC')->column('examine_etime');
                        $a_list_v['create_ctime'] = empty($change_time) ? $a_list_v['create_ctime'] : (is_array($change_time) ? $change_time[0] : $change_time);// 无变更时间(多条记录的取最新时间)的显示注册时间
                    }
                }
            }
            $this->assign('list',$agent_list);
            $this->assign('levelList',$levelList);
            $this->assign('last_g',$m_agents->getLastGeneration());
            $this->assign('search',$received->param());
            return $this->fetch('underAgent');
        } else {
            $this->error('参数错误');
        }
    }

    /**
     * 意向代理(done)
     */
    public function outsiderAgent()
    {
    	$received = request();
    	$a_id = $received->param('id');// 编号
    	$r_id = $received->param('rid');// 角色

    	if (!empty($a_id))
    	{
    		$m_agents = model('Agents');
    		$m_application = model('AgentApplications');
    		$m_agent_level = model('Agentlevel');
    		$role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
    		$m_index_agents = new IndexAgents();
    		$son_id = $m_index_agents->getSonIdByRole($a_id,$r_id);//

    		$agent_list = [];
    		if(!empty($son_id))
    		{
    			if($received->param('s_a_id'))
    			{
    				$where['agent_id'] = [['in',implode(',',$son_id)],['eq',$received->param('s_a_id')]];
    			}else{
    				$where = [
    				'agent_id'=>['in',implode(',', $son_id)]
    				];
    			}
    			if($received->param('name'))
    			{
    				$where['name'] = ['like','%'.$received->param('name','','trim').'%'];
    			}
    			if($received->param('wechat'))
    			{
    				$where['wechat'] = ['like','%'.$received->param('wechat','','trim').'%'];
    			}
    			if($received->param('phone'))
    			{
    				$where['phone'] = $received->param('phone','','trim');
    			}
    			if($received->param('generation'))
    			{
    				$where['generation'] = $received->param('generation','','trim');
    			}
    			$search_role = $received->param('role','','trim');

    			$where['role'] = ['eq',$search_role];

    			$agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
    			if($agent_list)
    			{
    				$m_order_reward = new IndexOrderReward();
    				foreach ($agent_list as $a_list_k => $a_list_v)
    				{
    					$a_list_v['role_lang']   = $role_lang[$a_list_v['role']];
    					$a_list_v['sales_money'] = count_team_orders_sales_total([$a_list_v['agent_id']]);// $m_order_reward->getSaleTotalByAgentId($a_list_v['agent_id']);/* 销售总额 */
    					$a_list_v['under_count'] = count($m_index_agents->getSons($a_list_v['agent_id'],$a_list_v['role'],2));// 直属代理数
    					$a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
    					// 获取已审核变更邀请人记录的时间
    					$change_time = $m_application->where(['status'=>1,'target'=>$a_id,'type'=>3,'a_id'=>$a_list_v['agent_id']])->order('examine_etime DESC')->column('examine_etime');
    					$a_list_v['create_ctime'] = empty($change_time) ? $a_list_v['create_ctime'] : (is_array($change_time) ? $change_time[0] : $change_time);// 无变更时间的显示注册时间
    				}
    			}
    		}
    		$this->assign('list',$agent_list);
    		$this->assign('role_lang',array_filter($role_lang));
    		$this->assign('last_g',$m_agents->getLastGeneration());
    		$this->assign('search',$received->param());
    		return $this->fetch('outsiderAgent');
    	} else {
    		$this->error('参数错误');
    	}
    }

    /**
     * 推荐代理(所有该代理商直接推荐的代理)(done)
     */
    public function recommendAgent()
    {
        $received = request();
        $a_id = $received->param('id');
        if(!empty($a_id))
        {
            $m_agents = model('Agents');
            $m_application = model('AgentApplications');
            $m_agent_level = model('Agentlevel');
            $role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
            $where = [
                'is_del'  => 0,
                'inviter' => $a_id,
            ];
            if($received->param('s_a_id'))
            {
                $where['agent_id'] = $received->param('a_id');
            }
            if($received->param('name'))
            {
                $where['name'] = ['like','%'.$received->param('name').'%'];
            }
            if($received->param('wechat'))
            {
                $where['wechat'] = ['like','%'.$received->param('wechat').'%'];
            }
            if($received->param('phone'))
            {
                $where['phone'] = $received->param('phone');
            }
            if($received->param('generation'))
            {
                $where['generation'] = $received->param('generation');
            }
            $search_role = $received->param('role');
            if(isset($search_role))
            {
                if($search_role >= 0)
                {
                    $where['role'] = ['eq',$search_role];
                }
            }
            $agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
            if($agent_list)
            {
                foreach ($agent_list as $a_list_k => $a_list_v)
                {
                    $a_list_v['role_lang']   = $role_lang[$a_list_v['role']];
                    $a_list_v['under_count'] = $m_agents->where(['role'=>['<',$a_list_v['role']],'family'=>['like','%'.$a_list_v['agent_id'].'%'],'is_del'=>0])->count();// 直属代理数
                    $a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
                    // 获取已审核变更邀请人记录的时间
                    $change_time = $m_application->where(['status'=>1,'target'=>$a_id,'type'=>3,'a_id'=>$a_list_v['agent_id']])->order('examine_etime DESC')->column('examine_etime');
                    $a_list_v['create_ctime'] = empty($change_time) ? $a_list_v['create_ctime'] : (is_array($change_time) ? $change_time[0] : $change_time);// 无变更时间的显示注册时间
                }
            }
            $this->assign('list',$agent_list);
            $this->assign('role_lang',array_filter($role_lang));
            $this->assign('last_g',$m_agents->getLastGeneration());
            $this->assign('search',$received->param());
            return $this->fetch('recommendAgent');
        }else{
            $this->error('参数错误');
        }
    }

    /**
     * 查看代理商信息(done)
     */
    public function agentInfo()
    {
        $received = request();
        $a_id = $received->param('a_id');
        if(!$a_id)
        {
            $this->redirect('agentsList');
        }
        $m_agents = model('Agents');
        $info = $m_agents->field('agent_id,name,phone,generation,role,inviter,nickname,wechat,sex,id_card,province,city,area,address,end_etime')->where(['agent_id'=>$a_id])->find();// 代理商信息
        $m_agent_level = model('Agentlevel');
        $role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
        if(!empty($info->inviter))
        {
            $name = $m_agents->where(['agent_id'=>$info->inviter])->column('name');
            $info->inviter = $name[0];
        }else{
            unset($info->inviter);
        }
        $financial_account = model('AgentFinancialAccount')->field('type,account,bank,name')->where(['a_id'=>$a_id,'is_del'=>0])->select();// 资金账户
        $accounts = [];
        if($financial_account)
        {
            foreach ($financial_account as $key => $val)
            {
                $accounts[$val['type']]['account'] = $val['account'];
                $accounts[$val['type']]['bank']    = $val['bank'];
                $accounts[$val['type']]['name']    = $val['name'];
            }
        }
        $this->assign('wechat_is_bind',model('Weixinusers')->getIsBandByAgentId($a_id));
        $this->assign('info',$info);
        $this->assign('account',$accounts);
        $this->assign('role_lang',$role_lang[$info->role]);
        return $this->fetch('agentInfo');
    }

    /**
     * 导出代理商(done)
     */
    public function exportAgent()
    {
        $received = request();
        $search['a_id']       = $received->param('a_id');
        $search['name']       = $received->param('name','','trim');
        $search['phone']      = $received->param('phone','','trim');
        $search['generation'] = $received->param('generation');
        $search['role']       = $received->param('role');
        $search['status']     = $received->param('status');
        $search['begin']      = $received->param('begin');
        $search['end']        = $received->param('end');
        $status_lang = ['会员','申请注册','申请升级','已确认','驳回','取消'];
        $data_field = array('代理商编号','姓名','微信号','手机号','角色','状态','团队人数','销售总额','最低库存','当前库存','申请时间');
        $filename = '代理商列表';
        $where = [];// 查询条件
        if(!empty($search['a_id']))
        {
            $where['agent_id'] = $search['a_id'];
        }
        if(!empty($search['name']))
        {
            $where['name'] = ['like','%'.$search['name'].'%'];
        }
        if(!empty($search['phone']))
        {
            $where['phone'] = ['like','%'.$search['phone'].'%'];
        }
        if(!empty($search['generation']))
        {
            $where['generation'] = $search['generation'];
        }
        if(in_array($search['role'], [0,1,2,3,4,5,6]) && isset($search['role']))
        {
            $where['role'] = $search['role'];
        }
        if(in_array($search['status'], [0,1,2,3,4,5]) && isset($search['status']))
        {
            $where['status'] = $search['status'];
        }
        if(!empty($search['begin']) && !empty($search['end']))
        {
            $where['create_ctime'] = ['between',[$search['begin'],date('Y-m-d',strtotime($search['end'].' + 1 day'))]];
        }else if(empty($search['begin']) && !empty($search['end'])){
            $where['create_ctime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['end'].' + 1 day'))]];
        }else if(!empty($search['begin']) && empty($search['end'])){
            $where['create_ctime'] = ['between',[$search['begin'],date('Y-m-d',time()+86400)]];
        }
        $data = array();// 输出数据
        $list = model('Agents')->where($where)->order('create_ctime desc')->select();// 查询所有数据
        foreach($list as $k=>$v)
        {
            $limit = model('Agentrewardagency')->where(['role'=>$v['role']])->column('lowest_limit');
            $data[$k]['a_id']         = $v['agent_id'];
            $data[$k]['name']         = $v['name'];
            $data[$k]['wechat']       = $v['wechat'];
            $data[$k]['phone']        = $v['phone'];
            $data[$k]['rolename']     = $v['generation'].'代'.get_reward_levelname($v['role']);
            $data[$k]['status']       = $status_lang[$v['status']];
            $data[$k]['team']         = model('Agents')->where(['role'=>['lt',$v['role']],'family'=>['like','%'.$v['agent_id'].'%']])->count();
            $data[$k]['sales']        = 0;// 销量(CYL)
            $data[$k]['limit']        = $limit ? $limit[0] : 0;
            $data[$k]['stock_money']  = $v['stock_money'];
            $data[$k]['create_ctime'] = $v['create_ctime'];
        }
        $this->exportexcel($data,$data_field,$filename);
    }

    /**
     * 导出代理商申请(done)
     */
    public function exportApp()
    {
        $received = request();
        $type = $received->param('type');// 导出类型1:待审2通过3驳回
        $search['a_id']       = $received->param('a_id');
        $search['name']       = $received->param('name');
        $search['generation'] = $received->param('generation');
        $search['role']       = $received->param('role');
        $search['begin']      = $received->param('begin');
        $search['end']        = $received->param('end');
        $where = ['p.type'=>['in','1,2,3,4'],'p.is_del'=>0,'a.is_del'=>0];
        if(!empty($search['a_id']))
        {
            $where['p.agent_id'] = $search['a_id'];
        }
        if(!empty($search['name']))
        {
            $where['a.name'] = ['like','%'.$search['name'].'%'];
        }
        if(in_array($search['role'], [0,1,2,3,4,5,6]) && isset($search['role']))
        {
            $where['a.role'] = $search['role'];
        }
        if(!empty($search['generation']))
        {
            $where['a.generation'] = $search['generation'];
        }
        if(!empty($search['begin']) && !empty($search['end']))
        {
            $where['p.create_ctime'] = ['between',[$search['begin'],date('Y-m-d',strtotime($search['end'].' + 1 day'))]];
        }else if(empty($search['begin']) && !empty($search['end'])){
            $where['p.create_ctime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['end'].' + 1 day'))]];
        }else if(!empty($search['begin']) && empty($search['end'])){
            $where['p.create_ctime'] = ['between',[$search['begin'],date('Y-m-d',time()+86400)]];
        }
        // 导出设置
        switch ($type)
        {
            case '1':
                $data_field = array('代理商ID','姓名','代数','身份','状态','团队人数','销售总额','剩余库存','申请时间','上级','支付金额','说明');
                $filename = '代理商申请记录-待审核';
                $where['p.status'] = 0;
                break;
            case '2':
                $data_field = array('代理商ID','姓名','代数','身份','团队人数','销售总额','剩余库存','申请时间','上级','支付金额','审核时间');
                $filename = '代理商申请记录-已审核';
                $where['p.status'] = 1;
                break;
            case '3':
                $data_field = array('代理商ID','姓名','代数','身份','团队人数','销售总额','剩余库存','申请时间','上级','支付金额','审核时间');
                $filename = '代理商申请记录-驳回';
                $where['p.status'] = 2;
                break;
            default:
                # code...
                break;
        }
        $data = array();// 输出数据
        $list = model('AgentApplications')->alias('p')->field('p.id,p.a_id,p.create_ctime,p.examine_etime,p.type,p.target,p.remarks,p.status,p.money,p.img,p.apply_by_id,a.name,a.phone,a.wechat,a.generation,a.role,a.stock_money,a2.name AS apply_name,a2.phone AS apply_phone')->join('agents a','p.a_id=a.agent_id AND a.is_del=0')->join('agents a2','p.apply_by_id=a2.agent_id AND a2.is_del=0','LEFT')->where($where)->order('p.create_ctime desc')->select();// 查询所有数据
        foreach($list as $k=>$v)
        {
            $data[$k]['a_id']       = $v['a_id'];
            $data[$k]['name']       = $v['name'];
            $data[$k]['generation'] = $v['generation'];
            $data[$k]['rolename']   = get_reward_levelname($v['role']);
            if($type == 1)
            {
                $data[$k]['status'] = $v['status'];
            }
            $data[$k]['team']  = model('Agents')->where(['role'=>['lt',$v['role']],'family'=>['like','%'.$v['a_id'].'%']])->count();
            $data[$k]['sales'] = 0;// 销量(CYL)
            $data[$k]['stock_money']  = $v['stock_money'];
            $data[$k]['create_ctime'] = $v['create_ctime'];
            $data[$k]['agent'] = $v['apply_by_id'] ? '手机号:'.$v['apply_phone'].' 姓名:'.$v['apply_name'].' ID:'.$v['apply_by_id'] : '';
            $data[$k]['money'] = $v['money'];
            if($type == 1)
            {
                $data[$k]['remark'] = $v['remarks'];
            }
            if($type == 2 || $type == 3)
            {
                $data[$k]['examine_etime'] = $v['examine_etime'];
            }
        }
        $this->exportexcel($data,$data_field,$filename);
    }

    /**
     * 导出推荐的代理
     */
    public function exportRecommend()
    {
        $received = request();
        $a_id = $received->param('id');
        if(empty($a_id))
        {
            return "";
        }
        $search['a_id']       = $received->param('s_a_id');
        $search['name']       = $received->param('name','','trim');
        $search['phone']      = $received->param('phone','','trim');
        $search['generation'] = $received->param('generation');
        $search['role']       = $received->param('role');
        $search['wechat']     = $received->param('wechat','','trim');
        $data_field = array('代理商编号','姓名','微信号','手机号','角色','团队','销售额');
        $filename = '推荐代理-'.$a_id;
        $data = array();// 输出数据
        $m_agents = model('Agents');
        $m_application = model('AgentApplications');
        $m_agent_level = model('Agentlevel');
        $role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
        $where = [
            'is_del'  => 0,
            'inviter' => $a_id,
        ];
        if(!empty($search['a_id']))
        {
            $where['agent_id'] = $search['a_id'];
        }
        if(!empty($search['name']))
        {
            $where['name'] = ['like','%'.$search['name'].'%'];
        }
        if(!empty($search['wechat']))
        {
            $where['wechat'] = ['like','%'.$search['wechat'].'%'];
        }
        if(!empty($search['phone']))
        {
            $where['phone'] = $search['phone'];
        }
        if(!empty($search['generation']))
        {
            $where['generation'] = $search['generation'];
        }
        if(isset($search['role']) && $search['role'] >= 0)
        {
            $where['role'] = ['eq',$search['role']];
        }
        $agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->select();
        if($agent_list)
        {
            $m_index_agents = new IndexAgents();
            $m_order_reward = new IndexOrderReward();
            foreach ($agent_list as $a_list_k => $a_list_v)
            {
                $a_list_v['sales_money'] = count_team_orders_sales_total([$a_list_v['agent_id']]);// $m_order_reward->getSaleTotalByAgentId($a_list_v['agent_id']);/* 销售总额 */
                $a_list_v['under_count'] = count($m_index_agents->getSons($a_list_v['agent_id'],$a_list_v['role'],2));// 直属代理数
                $a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
                // 获取已审核变更邀请人记录的时间
                // $change_time = $m_application->where(['status'=>1,'target'=>$a_id,'type'=>3,'a_id'=>$a_list_v['agent_id']])->column('examine_etime');
                // $a_list_v['create_ctime'] = empty($change_time) ? $a_list_v['create_ctime'] : $change_time;// 无变更时间的显示注册时间
                $data[$a_list_k]['a_id']   = $a_list_v['agent_id'];
                $data[$a_list_k]['name']   = $a_list_v['name'];
                $data[$a_list_k]['wechat'] = $a_list_v['wechat'];
                $data[$a_list_k]['phone']  = $a_list_v['phone'];
                $data[$a_list_k]['rolename'] = $a_list_v['generation'].'代'.get_reward_levelname($a_list_v['role']);
                $data[$a_list_k]['team'] = '直属代理:'.$a_list_v['under_count'].'人 推荐代理:'.$a_list_v['recommend_count'].'人';
                $data[$a_list_k]['sales'] = "'￥".$a_list_v['sales_money']."'";
            }
        }
        $this->exportexcel($data,$data_field,$filename);
    }

    /**
     * 导出直属代理
     */
    public function exportUnder()
    {
        $received = request();
        $id = $received->param('id');
        $rid = $received->param('rid');
        if(!isset($id) && !isset($rid))
        {
            return "";
        }
        $search['a_id']       = $received->param('s_a_id');
        $search['name']       = $received->param('name','','trim');
        $search['phone']      = $received->param('phone','','trim');
        $search['generation'] = $received->param('generation');
        $search['role']       = $received->param('role');
        $search['wechat']     = $received->param('wechat','','trim');
        $data_field = array('代理商编号','姓名','微信号','手机号','角色','团队','销售额');
        $filename = '直属代理-'.$id;
        $m_agents = model('Agents');
        $m_application = model('AgentApplications');
        $m_agent_level = model('Agentlevel');
        $role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
        $m_index_agents = new IndexAgents();
        $son_id = $m_index_agents->getSons($id,$rid,2);// 获取所有子级代理(包含会员)
        $data = array();// 输出数据
        $agent_list = [];
        if(!empty($son_id))
        {
            if($received->param('s_a_id'))
            {
                $where['agent_id'] = [['in',implode(',',$son_id)],['eq',$received->param('s_a_id')]];
            }else{
                $where = [
                    'agent_id'=>['in',implode(',', $son_id)]
                ];
            }
            if($received->param('name'))
            {
                $where['name'] = ['like','%'.$received->param('name','','trim').'%'];
            }
            if($received->param('wechat'))
            {
                $where['wechat'] = ['like','%'.$received->param('wechat','','trim').'%'];
            }
            if($received->param('phone'))
            {
                $where['phone'] = $received->param('phone','','trim');
            }
            if($received->param('generation'))
            {
                $where['generation'] = $received->param('generation','','trim');
            }
            $search_role = $received->param('role','','trim');
            switch ($search_role)
            {
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                    $where['role'] = ['eq',$search_role];
                    break;
                default:

                    break;
            }
            $agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->select();
            if($agent_list)
            {
                $m_order_reward = new IndexOrderReward();
                foreach ($agent_list as $a_list_k => $a_list_v)
                {
                    $a_list_v['sales_money'] = count_team_orders_sales_total([$a_list_v['agent_id']]);// $m_order_reward->getSaleTotalByAgentId($a_list_v['agent_id']);/* 销售总额 */
                    $a_list_v['under_count'] = count($m_index_agents->getSons($a_list_v['agent_id'],$a_list_v['role'],2));// 直属代理数
                    $a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
                    $data[$a_list_k]['a_id']   = $a_list_v['agent_id'];
                    $data[$a_list_k]['name']   = $a_list_v['name'];
                    $data[$a_list_k]['wechat'] = $a_list_v['wechat'];
                    $data[$a_list_k]['phone']  = $a_list_v['phone'];
                    $data[$a_list_k]['rolename'] = $a_list_v['generation'].'代'.get_reward_levelname($a_list_v['role']);
                    $data[$a_list_k]['team'] = '直属代理:'.$a_list_v['under_count'].'人 推荐代理:'.$a_list_v['recommend_count'].'人';
                    $data[$a_list_k]['sales'] = "'￥".$a_list_v['sales_money']."'";
                }
            }
        }
        $this->exportexcel($data,$data_field,$filename);
    }

    /**
     * 导出意向代理
     */
    public function exportOutsider()
    {
    	$received = request();
    	$id = $received->param('id');
    	$rid = $received->param('rid');
    	if(!isset($id) && !isset($rid))
    	{
    		return "";
    	}
    	$search['a_id']       = $received->param('s_a_id');
    	$search['name']       = $received->param('name','','trim');
    	$search['phone']      = $received->param('phone','','trim');
    	$search['generation'] = $received->param('generation');
    	$search['role']       = $received->param('role');
    	$search['wechat']     = $received->param('wechat','','trim');
    	$data_field = array('代理商编号','姓名','微信号','手机号','角色','团队','销售额');
    	$filename = '会员-'.$id;
    	$m_agents = model('Agents');
    	$m_application = model('AgentApplications');
    	$m_agent_level = model('Agentlevel');
    	$role_lang = $m_agent_level->getRoleName();// 角色定义的名称数组
    	$m_index_agents = new IndexAgents();

    	$son_id = $m_index_agents->getSonIdByRole($id,$rid);// 获取所有子级代理(包含会员)

    	$data = array();// 输出数据
    	$agent_list = [];
    	if(!empty($son_id))
    	{
    		if($received->param('s_a_id'))
    		{
    			$where['agent_id'] = [['in',implode(',',$son_id)],['eq',$received->param('s_a_id')]];
    		}else{
    			$where = [
    			'agent_id'=>['in',implode(',', $son_id)]
    			];
    		}
    		if($received->param('name'))
    		{
    			$where['name'] = ['like','%'.$received->param('name','','trim').'%'];
    		}
    		if($received->param('wechat'))
    		{
    			$where['wechat'] = ['like','%'.$received->param('wechat','','trim').'%'];
    		}
    		if($received->param('phone'))
    		{
    			$where['phone'] = $received->param('phone','','trim');
    		}
    		if($received->param('generation'))
    		{
    			$where['generation'] = $received->param('generation','','trim');
    		}
    		$search_role = $received->param('role','','trim');
    		switch ($search_role)
    		{
    			case '0':
    			case '1':
    			case '2':
    			case '3':
    			case '4':
    			case '5':
    			case '6':
    			case '7':
    				$where['role'] = ['eq',$search_role];
    				break;
    			default:

    				break;
    		}
    		$agent_list = $m_agents->field('agent_id,name,generation,role,wechat,phone,create_ctime')->where($where)->order('agent_id asc')->select();
    		if($agent_list)
    		{
    			$m_order_reward = new IndexOrderReward();
    			foreach ($agent_list as $a_list_k => $a_list_v)
    			{
    				$a_list_v['sales_money'] = count_team_orders_sales_total([$a_list_v['agent_id']]);// $m_order_reward->getSaleTotalByAgentId($a_list_v['agent_id']);/* 销售总额 */
    				$a_list_v['under_count'] = count($m_index_agents->getSons($a_list_v['agent_id'],$a_list_v['role'],2));// 直属代理数
    				$a_list_v['recommend_count'] = $m_agents->where(['is_del'=>0,'inviter'=>$a_list_v['agent_id']])->count();// 推荐代理数
    				$data[$a_list_k]['a_id']   = $a_list_v['agent_id'];
    				$data[$a_list_k]['name']   = $a_list_v['name'];
    				$data[$a_list_k]['wechat'] = $a_list_v['wechat'];
    				$data[$a_list_k]['phone']  = $a_list_v['phone'];
    				$data[$a_list_k]['rolename'] = $a_list_v['generation'].'代'.get_reward_levelname($a_list_v['role']);
    				$data[$a_list_k]['team'] = '直属代理:'.$a_list_v['under_count'].'人 推荐代理:'.$a_list_v['recommend_count'].'人';
    				$data[$a_list_k]['sales'] = "'￥".$a_list_v['sales_money']."'";
    			}
    		}
    	}
    	$this->exportexcel($data,$data_field,$filename);
    }

    /**
     * 变更上级代理
     */
    public function agentschangerelationship()
    {
        return $this->fetch('agentsChangeRelationship');
    }

    /**
     * 代理商查询
     */
    public function agentSreach(Request $request)
    {
        $agent=model('Agents');
        $sreach=$request->param('searchInfo');
        $a_id = $request->param('id','0');
        $data=$agent->field('agent_id,name,phone')->where(['agent_id|wechat|phone'=>['like','%'.$sreach.'%'],'agent_id'=>['neq',$a_id]])->select();
        if(count($data)==0){
            $data=-1;
        }
        return json_encode($data);
    }

    /**
     * 变更代理关系
     */
    public function changerelationshop(Request $request)
    {
        $agent_id=$request->param('agent_id');
        $inviter_id=$request->param('inviter_id');
        $agent=model('Agents');
        $agentInfo=$agent->field('inviter,generation')->where('agent_id',$agent_id)->find();
        $oldInviter=empty($agentInfo->inviter)?'':$agentInfo->inviter;
        $result=0;
        $edit_data=array();
       // return json_encode($oldInviter);
        /* 对比邀请人字段是否更改 S */
        if ($inviter_id != $oldInviter)
        {
            $edit_data['inviter'] = $inviter_id;
            // 非空时查询该邀请人的族谱
            if(!empty($edit_data['inviter']))
            {
                $edit_data['family'] = $agent->getNewFamily([$edit_data['inviter']]);
                $edit_data['generation'] = count(explode(',', $edit_data['family'])) + 1;// 代数
            }else{
                $edit_data['family'] = '';
                $edit_data['generation'] = 1;// 代数
            }
            // 代数是否变更
            $g_change_num = 0;// 代数变更量
            if($edit_data['generation'] != $agentInfo->generation)
            {
                $g_change_num = $edit_data['generation'] - $agentInfo->generation;
            }

            // 2018-07-11 CYL 添加邀请人变更记录
            $change_relation_data['a_id']     = $agent_id;
            $change_relation_data['examiner'] = session('username');
            $change_relation_data['remarks']  = '邀请人变更';
            $change_relation_data['now']      = $agentInfo->inviter;// 当前上级
            $change_relation_data['now']      = isset($change_relation_data['now']) ? $change_relation_data['now'] : '-1';
            $change_relation_data['target']   = $edit_data['inviter'];// 变后上级
            $change_relation_data['now_g']    = $agentInfo->generation;// 当前代数
            $change_relation_data['target_g'] = $edit_data['generation'];// 变后代数
            model('AgentApplications')->recordChangeRelationLog($change_relation_data);
        }
        /* 对比邀请人字段是否更改 E */
        // unset($edit_data['endTime']);
        $edit_result = $agent->save($edit_data,['agent_id'=>$agent_id]);// 修改
        // 修改成功并且修改了邀请人信息,更新所有下级会员的族谱
        if($edit_result && $inviter_id != $oldInviter)
        {
            $son_list = $agent->getAllSonAgentList([$agent_id]);
            if(count($son_list) > 0)
            {
                foreach ($son_list as $son_list_k => $son_list_v)
                {
                    $son_list_family = trim($edit_data['family'].','.substr($son_list_v['family'], strpos($son_list_v['family'], $agent_id)),',');
                    $agent->save(['family'=>$son_list_family,'generation'=>$son_list_v['generation']+$g_change_num],['agent_id'=>$son_list_v['a_id']]);

                    // 2018-07-11 CYL 添加邀请人变更记录
                    $change_relation_data = [];
                    $change_relation_data['a_id']     = $son_list_v['a_id'];
                    $change_relation_data['examiner'] = session('username');
                    $change_relation_data['remarks']  = '上级的邀请人变更,本级仅关联变更代数和族谱';
                    $change_relation_data['now']      = $son_list_v['inviter'];// 当前上级
                    $change_relation_data['now']      = isset($change_relation_data['now']) ? $change_relation_data['now'] : '-1';
                    $change_relation_data['target']   = $change_relation_data['now'];// 变后上级
                    $change_relation_data['now_g']    = $son_list_v['generation'];// 当前代数
                    $change_relation_data['target_g'] = $son_list_v['generation']+$g_change_num;// 变后代数
                    model('AgentApplications')->recordChangeRelationLog($change_relation_data);
                }
            }
        }
        return json_encode(1);
    }

    /**
     * 升级代理身份
     */
    public function agentsUpgradeIdentity()
    {
        return $this->fetch('agentsUpgradeIdentity');
    }

    /**
     * 升级
     */
    public function upgradeIdentity(Request $request)
    {
        $agent_id=$request->param('agent_id');
        $role_id=$request->param('role_id');


        //校验下，如果是降级，那么发个消息通知
        $this->degradationMessage($agent_id,$role_id);

        //获取下当前的用户等级
        $agent=model('Agents');
        $change=model('Agentchangerole');

        //插入到级别变动记录
        $userInfo=$agent->find($agent_id);
        $data=array();
        $userInfo['role']>$role_id && $data['type']=1;
        $userInfo['role']<$role_id && $data['type']=2;
        if(isset($data['type'])) {
	        $data['create_time']=time();
	        $data['agent_id']=$agent_id;
	        $data['before_role']=$userInfo['role'];
	        $data['after_role']=$role_id;
	        $data['reason']=2;
	        $data['remark']="操作人:".Session::get('username');
	        $change->save($data);
        }

        //变动级别
        $result=model('Agents')->where('agent_id',$agent_id)->update(['role'=> $role_id,'is_use'=>1,'status'=>3]);

        return json_encode($result);
    }

    /**
     * 初始化变更上级代理和升级代理身份信息
     */
    public function initChangeAndUpgradeData(Request $request)
    {
        $agentsId=$request->param('agentsId');
        $agent=model('Agents');
        $infoData=$agent->getAgents($agentsId);
        $data['info']=$infoData;
        $data['roleName']=get_reward_levelname($infoData['role']);
        $data['role_arr']=model('Agentlevel')->order('id', 'desc')->select();
        $data['agent_arr']=$this->allAgent();
        $inviterName=$agent->field('name')->where('agent_id',$infoData['inviter'])->find();
        empty($inviterName) && $inviterName='无';
        $data['inviterName']=$inviterName;

        return json_encode($data);
    }

    public function allAgent($id=0)
    {
		$data=model('Agents')->field('agent_id,name,phone')->where(['role'=>['gt',0],'is_del'=>0,'agent_id'=>['neq',$id]])->select();
		return json_encode($data);
    }

    //降级发通知
    private function degradationMessage($agent_id,$role)
    {
    	$agents=model('Agents');
    	$info=$agents->field('agent_id,role,phone')->find($agent_id);

    	if($info['role']>$role) {


    		$mdata=array();
    		$mdata['first']='您的身份已经变更！';
    		$mdata['account']=$info['phone'];
    		$mdata['time']=date('Y-m-d H:i:s');
    		$mdata['type']='身份变更';
    		$mdata['remark']='现降低您的身份，请重新登录账户查询';

    		//给下单人发送发货通知
    		message_notification(3,$info['agent_id'],$mdata);
    	}
    }

    /**
     * 代理商排行榜
     */
    public function agentsRank($type = 1)
    {
        // 模板文件名
        $rank_list = [
            1 => 'salesrank',       // 销量排名
            2 => 'achievementsrank',// 绩效奖励排名
            3 => 'rewardsrank',     // 总奖励排名
            4 => 'teamrank',        // 代理团队排名
            5 => 'recommendrank',   // 推荐人数排名
        ];
        $received = request();
        $search   = [];// 查询条件
        $agent_search = [];
        $search_aid   = $received->param('a_id','','trim');
        $search_name  = $received->param('name','','trim');
        $search_phone = $received->param('phone','','trim');
        $search_role  = $received->param('role',null,'trim');
        $search_begin = $received->param('begin','','trim');
        $search_end   = $received->param('end','','trim');
        $search_month = $received->param('month','','trim');
        $search_month = empty($search_month) ? date('Y-m') : $search_month;
        if (isset($search_aid) && !empty($search_aid))
        {
            $search['a_id'] = $search_aid;
        }
        if(isset($search_name) && !empty($search_name))
        {
            $agent_search['name'] = ['like','%'.$search_name.'%'];
        }
        if(isset($search_phone) && !empty($search_phone))
        {
            $agent_search['phone'] = $search_phone;
        }
        if(isset($search_role) && in_array($search_role,[0,1,2,3,4,5,6]))
        {
            $agent_search['role'] = $search_role;
        }
        switch ($type)
        {
            case 2:
                $y_m = explode('-',$search_month);// 年月
                $reward_search['year']   = $y_m[0];
                $reward_search['month']  = $y_m[1];
                $reward_search['Agentperformancereward.status'] = 2;
                if(isset($search['a_id']))
                {
                    $reward_search['Agentperformancereward.agent_id'] = $search['a_id'];
                }
                $order = 'performance_profit DESC,agent_id ASC';
                $list  = model('Agentperformancereward')->hasWhere('agents',$agent_search)->where($reward_search)->order($order)->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                break;
            case 3:
                if(!empty($search_begin) && !empty($search_end))
                {
                    $agent_search['create_ctime'] = ['between',[$search_begin,$search_end]];
                }
                $order = 'rewards_money DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                break;
            case 4:
                $order = 'team_num DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                break;
            case 5:
                $order = 'recommend_num DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                break;
            case 1:
            default:
                $order = 'sales_money DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);
                break;
        }
        $this->assign('role_lang',model('Agentlevel')->getRoleName());
        $this->assign('type',$type);
        $this->assign('list',$list);
        $this->assign('search',$received->param());
        return $this->fetch($rank_list[$type]);
    }

    /**
     * 导出代理商排行榜
     */
    public function exportRank($type = 1)
    {
        header("Content-type: text/html; charset=utf-8");
        // 模板文件名
        $rank_list = [
            1 => '销量排名',
            2 => '绩效奖励排名',
            3 => '总奖励排名',
            4 => '代理团队排名',
            5 => '推荐人数排名',
        ];
        $received = request();
        $search   = [];// 查询条件
        $agent_search = [];
        $search_aid   = $received->param('a_id','','trim');
        $search_name  = $received->param('name','','trim');
        $search_phone = $received->param('phone','','trim');
        $search_role  = $received->param('role',null,'trim');
        $search_begin = $received->param('begin','','trim');
        $search_end   = $received->param('end','','trim');
        $search_month = $received->param('month','','trim');
        $search_month = empty($search_month) ? date('Y-m') : $search_month;
        if (isset($search_aid) && !empty($search_aid))
        {
            $search['a_id'] = $search_aid;
        }
        if(isset($search_name) && !empty($search_name))
        {
            $agent_search['name'] = ['like','%'.$search_name.'%'];
        }
        if(isset($search_phone) && !empty($search_phone))
        {
            $agent_search['phone'] = $search_phone;
        }
        if(isset($search_role) && in_array($search_role,[0,1,2,3,4,5,6]))
        {
            $agent_search['role'] = $search_role;
        }
        $role_lang = model('Agentlevel')->getRoleName();
        $data = [];// 导出数据
        switch ($type)
        {
            case 2:
                $data_field = ['排名','代理商ID','姓名','联系方式','身份','绩效奖励'];// 字段
                $y_m = explode('-',$search_month);// 年月
                $reward_search['year']   = $y_m[0];
                $reward_search['month']  = $y_m[1];
                $reward_search['Agentperformancereward.status'] = 2;
                if(isset($search['a_id']))
                {
                    $reward_search['Agentperformancereward.agent_id'] = $search['a_id'];
                }
                $order = 'performance_profit DESC,agent_id ASC';
                $list  = model('Agentperformancereward')->hasWhere('agents',$agent_search)->where($reward_search)->order($order)->select();
                foreach ($list as $key => $val)
                {
                    $cache = [];
                    $cache['rank']  = $key + 1;
                    $cache['a_id']  = $val['a_id'];
                    $cache['name']  = $val->agents->name.'('.$val->agents->nickname.')';
                    $cache['phone'] = $val->agents->phone;
                    $cache['role']  = $val->agents->generation.'代'.$role_lang[$val->agents->role];
                    $cache['total'] = '￥'.$val->agentperformancereward->performance_profit;
                    $data[] = $cache;
                }
                $this->exportexcel($data,$data_field,$rank_list[$type].$search_month);
                break;
            case 3:
                $data_field = ['排名','代理商ID','姓名','联系方式','身份','总奖励金额'];// 字段
                if(!empty($search_begin) && !empty($search_end))
                {
                    $agent_search['create_ctime'] = ['between',[$search_begin,$search_end]];
                }
                $order = 'rewards_money DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->select();
                foreach ($list as $key => $val)
                {
                    $cache = [];
                    $cache['rank']  = $val['reward_rank'];
                    $cache['a_id']  = $val['a_id'];
                    $cache['name']  = $val->agents->name.'('.$val->agents->nickname.')';
                    $cache['phone'] = $val->agents->phone;
                    $cache['role']  = $val->agents->generation.'代'.$role_lang[$val->agents->role];
                    $cache['total'] = '￥'.$val['rewards_money'];
                    $data[] = $cache;
                }
                $this->exportexcel($data,$data_field,$rank_list[$type]);
                break;
            case 4:
                $data_field = ['排名','代理商ID','姓名','联系方式','身份','代理人数'];// 字段
                $order = 'team_num DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->select();
                foreach ($list as $key => $val)
                {
                    $cache = [];
                    $cache['rank']  = $val['team_rank'];
                    $cache['a_id']  = $val['a_id'];
                    $cache['name']  = $val->agents->name.'('.$val->agents->nickname.')';
                    $cache['phone'] = $val->agents->phone;
                    $cache['role']  = $val->agents->generation.'代'.$role_lang[$val->agents->role];
                    $cache['total'] = $val['team_num'];
                    $data[] = $cache;
                }
                $this->exportexcel($data,$data_field,$rank_list[$type]);
                break;
            case 5:
                $data_field = ['排名','代理商ID','姓名','联系方式','身份','推荐人数'];// 字段
                $order = 'recommend_num DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->select();
                foreach ($list as $key => $val)
                {
                    $cache = [];
                    $cache['rank']  = $val['recommend_rank'];
                    $cache['a_id']  = $val['a_id'];
                    $cache['name']  = $val->agents->name.'('.$val->agents->nickname.')';
                    $cache['phone'] = $val->agents->phone;
                    $cache['role']  = $val->agents->generation.'代'.$role_lang[$val->agents->role];
                    $cache['total'] = $val['recommend_num'];
                    $data[] = $cache;
                }
                $this->exportexcel($data,$data_field,$rank_list[$type]);
                break;
            case 1:
            default:
                $data_field = ['排名','代理商ID','姓名','联系方式','身份','总销售额'];// 字段
                $order = 'sales_money DESC,a_id ASC';
                $list  = model('Agentrank')->hasWhere('agents',$agent_search)->where($search)->order($order)->select();
                foreach ($list as $key => $val)
                {
                    $cache = [];
                    $cache['rank']  = $val['sale_rank'];
                    $cache['a_id']  = $val['a_id'];
                    $cache['name']  = $val->agents->name.'('.$val->agents->nickname.')';
                    $cache['phone'] = $val->agents->phone;
                    $cache['role']  = $val->agents->generation.'代'.$role_lang[$val->agents->role];
                    $cache['total'] = '￥'.$val['sales_money'];
                    $data[] = $cache;
                }
                $this->exportexcel($data,$data_field,$rank_list[$type]);
                break;
        }
    }

    /**
     * 库存明细
     */
    public function inventory($a_id = '')
    {
        if($a_id)
        {
            $received = request();
            $role_lang = model('Agentlevel')->getRoleName(1);
            $info = model('agents')->where(['agent_id'=>$a_id])->find();
            $info['role'] = $role_lang[$info['role']];
            $condition = ['`sc`.`agent_id`'=>$a_id,'`sc`.`status`'=>2,'`sc`.`change_type`'=>['in','1,2,3,4,5,6']];
            /*if($received->param('number'))
            {
                $condition['`sc`.`order_number`'] = $received->param('number');
            }
            if($received->param('money'))
            {
                $condition['`sc`.`money`'] = $received->param('money');
            }
            $type = $received->param('type');
            switch ($type)
            {
                case '3':
                    $condition['`aor`.`reward_type`'] = 3;
                    break;
                case '4':
                    $condition['`aor`.`reward_type`'] = 4;
                    break;
                case '0':
                default:
                    $condition['`aor`.`reward_type`'] = ['in','3,4'];
                    break;
            }
            $begin = $received->param('begin');
            $end   = $received->param('end');
            if($begin || $end)
            {
                $begin = empty($begin) ? time() : strtotime($begin);
                $end   = empty($end) ? time() : strtotime($end);
                $time  = [$begin,$end];
                sort($time,SORT_NUMERIC);
                $condition['ao.create_time'] = ['between',$time];
            }*/
            $list = model('Agentstockchange')->alias('sc')->field('ao.agent_id AS a_id,ao.create_time,sc.id,sc.order_number,sc.change_type,sc.money,sc.change_after,sc.remark,sc.audit_time,aor.reward_type,aor.sales_amount,aor.directsale_reward,aor.wholesale_reward')->join('agent_orders ao','sc.order_number=ao.order_number','left')->join('agent_order_reward aor','sc.order_number = aor.order_number AND aor.agent_id=sc.agent_id AND `aor`.`reward_type` IN (2, 3)','left')->where($condition)->order('sc.create_time ASC')->select();/*paginate(config('paginate.list_rows'),false,['query'=>$received->param()]);*/
            $list = collection($list)->toArray();
            if(!empty($list))
            {
                foreach ($list as $key => &$val)
                {
                    $val['create_time'] = empty($val['audit_time']) ? $val['create_time'] : date('Y-m-d H:i:s',$val['audit_time']);
                }
                $list = array_sequence(collection($list)->toArray(),'create_time','SORT_ASC');
            }
            $this->assign('list',$list);
            $this->assign('info',$info);
            $this->assign('lang',[1=>'申请充值',2=>'后台充值',4=>'加库存',5=>'申报减库存',6=>'升级加库存']);
            $this->assign('a_id',$a_id);
            $this->assign('search',$received->param());
            return $this->fetch();
        }else{
            $this->redirect('agentsList');
        }
    }

    /**
     * 库存明细导出
     */
    public function exportInventory($a_id = '')
    {
        header("Content-type: text/html; charset=utf-8");
        if(!$a_id)
        {
            return ['error'=>['msg'=>'参数错误']];
        }
        $received = request();
        $condition = ['`sc`.`agent_id`'=>$a_id,'`sc`.`status`'=>2,'`sc`.`change_type`'=>['in','1,2,3,4,5,6']];
        /*if($received->param('number'))
        {
            $condition['`sc`.`order_number`'] = $received->param('number');
        }
        if($received->param('money'))
        {
            $condition['`sc`.`money`'] = $received->param('money');
        }
        $type = $received->param('type');
        switch ($type)
        {
            case '3':
                $condition['`aor`.`reward_type`'] = 3;
                break;
            case '4':
                $condition['`aor`.`reward_type`'] = 4;
                break;
            case '0':
            default:
                $condition['`aor`.`reward_type`'] = ['in','3,4'];
                break;
        }
        $begin = $received->param('begin');
        $end = $received->param('end');
        if($begin || $end)
        {
            $begin = empty($begin) ? time() : strtotime($begin);
            $end   = empty($end) ? time() : strtotime($end);
            $time  = [$begin,$end];
            sort($time,SORT_NUMERIC);
            $condition['ao.create_time'] = ['between',$time];
        }*/
        $lang = [1=>'申请充值',2=>'后台充值',4=>'加库存',5=>'申报减库存',6=>'升级加库存'];
        $list = model('Agentstockchange')->alias('sc')->field('ao.agent_id AS a_id,ao.create_time,sc.order_number,sc.money,sc.change_after,sc.change_type,sc.remark,aor.reward_type,aor.sales_amount')->join('agent_orders ao','sc.order_number=ao.order_number','left')->join('agent_order_reward aor','sc.order_number = aor.order_number AND aor.agent_id=sc.agent_id','left')->where($condition)->order('ao.create_time ASC')->select();
        $data_field = ["代理商ID","变动类型","库存余额","订单编号","销售类型","交易金额","下单日期","备注"];// 字段
        $export_data = [];
        foreach ($list as $key => $value)
        {
            $cache = [];
            $cache['id']           = $value['a_id'];
            $cache['type']         = $lang[$value['change_type']];
            $cache['change_after'] = $value['change_after'];
            $cache['order_number'] = "'".$value['order_number'];
            $cache['reward_type']  = $value['reward_type'] == 3 ? '间接': '直销';
            $cache['money']        = $value['money'];
            $cache['create_time']  = $value['create_time'];
            $cache['remark']       = $value['remark'];
            $export_data[] = $cache;
        }
        $name = "库存明细-".$a_id;
        $this->exportexcel($export_data,$data_field,$name);
    }
}