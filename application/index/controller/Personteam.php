<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\admin\model\Agentlevel as AgentLevelModel;


class Personteam extends Controller
{
    protected $beforeActionList = [
        'first' => ['only'=>'agencylevel,agencyteam'],
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
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function agencyTeam()
    {
        //获取session用户信息
        $user = session('user');

        //操作代理商表
        $Agents=model('Agents');


        $resultID = model('Agents')->getSons($user['a_id'],$user['role'],4);//获取直属代理的信息
        $result=get_direct_lower_agent($user['a_id'],$user['role'],$resultID);

        $recommend = get_recommend_agent($user['a_id']);//获取推荐代理的信
        $data['role']=0;
        $data['is_del']=0;
        $data['inviter']=$user['a_id'];
        $rolevip = $Agents->where($data)->select(); //获取意向代理

        foreach ($rolevip as $k=>&$value){
            $value['create_ctime']=date('Y-m-d',strtotime($value['create_ctime']));

        }

        $this->assign('rolevip',$rolevip);
        $this->assign('recommend',$recommend);
        $this->assign('result',$result);
        $this->assign('spider',['title'=>'代理团队','content'=>'','key'=>'']);
        return $this->fetch('agencyTeam');
    }
    public function agencyLevel()
    {
        //获取session用户信息
        $user = session('user');
        //直属代理商 的数据获取逻辑方法。
        $agentLevel = model('Agentlevel');
        //操作代理商表
        $Agents=model('Agents');
        $received = request();
        $role = $received->param('role');
        $rank = $received->param('rank');
        $content = $received->param('content');
        $type=$received->param('type');

        if (!empty($role)){//直属代理用户列表
            $data=array();
            !empty($role) && $data=$agentLevel::get($role);
            $userlist = get_direct_role_agent($user['a_id'],$role);
        }else{            //推荐代理用户列表
            $data=array();
            !empty($rank) && $data=$agentLevel::get($rank);
            $userlist = get_recommend_list_agent($user['a_id'],$rank);
        }
         if(!empty($content)) {
            $ndata = array();
            $ndata['nickname|wechat|phone'] = ['like', '%' . $content . '%'];//加搜索条件
            $userinfo= $Agents->where($ndata)->select();

            $userId=array();
            $userrole=array();

            foreach ($userinfo as $k=>$v){
                $userId[]= $v['agent_id'];
                $userrole[] = $v['role'];
            }

            $data=array();
            if(!empty($userrole)){
                !empty($userrole) && $data=$agentLevel::get($userrole);
            }else{
                 $data['name'] = '直属';
            }

             $userdata = $Agents->where(array('agent_id'=>['in',$userId],'role'=>['in',$userrole]))->select();

             $uidLowAry=array();
             foreach($userdata as $k=>$v)
             {
                 $uidLowAry[]=$v['agent_id'];
             }

              //通过登录用户的role循环查询比他等级低的全部身份
             $role= $userrole;
             $roleList=$agentLevel->order('id','desc')->select();
             $levelAry=array();
             foreach($roleList as $k=>$v) {
                 $role>$v['id'] && $levelAry[]=$v['id'];
             }

             $userlist=array();
             foreach($uidLowAry as $k=>$v){
                 $tmpInfo=$Agents->find($v);
                 $userlist[$k]['agent_id']=$tmpInfo['agent_id'];
                 $userlist[$k]['head_img']=$tmpInfo['head_img'];
                 $userlist[$k]['generation']=$tmpInfo['generation'];
                 $userlist[$k]['nickname']=$tmpInfo['nickname'];
                 $userlist[$k]['directly_count']=count(model('Agents')->getSons($tmpInfo['agent_id'],$tmpInfo['role'],4));
                 $userlist[$k]['recommend_count']= $Agents->where(['inviter'=>$tmpInfo['agent_id']])->where('is_del','EQ',0)->count();

              }
         }

        $this->assign('data',$data);//详情页面数据
        $this->assign('userlist',$userlist);
        $this->assign('type',$type);//类型
        $this->assign('spider',['title'=>$data['name'].'代理','content'=>'','key'=>'']);
        return $this->fetch('agencyLevel');
    }
    //详细资料
    public function agencyData()
    {
        $received = request();
        $id = $received->param('id');
        $type = $received->param('type');

        //操作代理商表
        $Agents=model('Agents');

         $data=array();
        !empty($id) && $data=$Agents::get($id);

        $this->assign('data',$data);
        $this->assign('type',$type);
        return $this->fetch('agencyData');
    }

}
