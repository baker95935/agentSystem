<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use app\admin\model\Agentlevel as AgentLevelModel;

class Index extends Controller
{

    protected $beforeActionList = [
        'first' => ['only'=>'index,myachievement,myprivilege'],
        // 'second' => ['only'=>''],
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
            $tip = 1;
        }
        $this->assign('tip',$tip);
    }

    /**
     * 检查是否已登录,未登录的跳转至登录页(done)
     */
    protected function checkLogin()
    {
        if(!session('?user'))
        {// 未登录
            $this->redirect('Index/login');
        }
    }

    /**
     * 检查是否已登录,已登录的跳转目标链接(done)
     */
    protected function loginedRedirect($url = '')
    {
        $url = empty($url) ? 'Product/index' : $url;
        if(session('?user'))
        {// 已登录
            $this->redirect($url);
        }
    }

    /**
     * 个人中心首页(done)
     */
    public function index()
    {
    	//校验下 如果不是手机端打开，那么跳转到AMS后台
    	/*if(!isMobile()) {
    		$this->redirect('admin/Index/index');
    	}*/

        $this->checkLogin();
        $user = session('user');
        // 2018-07-27 CYL 会员禁止访问代理商个人中心页
        if($user['role'] == 0)
        {// 会员
            $this->redirect('Product/index');
        }
        $info = model('Agents')->getInfoByCondition(['agent_id'=>$user['a_id'],'is_del'=>0]);// 获取未删除代理商的信息
        if(!$info)
        {
            session('user',null);
            $this->redirect('Index/login');
        }

        // 检查是否有店铺记录
        $shop_log = model('Shop')->where(['a_id'=>$user['a_id']])->count();
        if($shop_log <= 0)
        {
            model('Shop')->insert(['a_id'=>$user['a_id'],'shop_name'=>empty($user['nickname']) ? $user['phone'] : $user['nickname']]);// 添加店铺记录
        }

        // 更新session
        $session = [
            'a_id'       => $user['a_id'],
            'phone'      => $info['phone'],
            'nickname'   => $info['nickname'],
            'name'       => $info['name'],
            'sex'        => $info['sex'],
            'wechat'     => $info['wechat'],
            'stock'      => $info['stock_money'],// 库存金额
            'province'   => $info['province'],
            'city'       => $info['city'],
            'area'       => $info['area'],
            'inviter'    => $info['inviter'],// 邀请人ID
            'generation' => $info['generation'],// 代数
            'role'       => $info['role'],// 身份
            'endtime'    => $info['end_etime'],// 有效期(暂未启用)
            'status'     => $info['status'],// 申请状态
            'head_img'   => $info['head_img'],// 头像
        ];
        session('user',$session);
        $family   = model('Agents')->getSons($user['a_id'],$user['role'],1);// 直属成员ID(包含已删除/已失效的)
        $family[] = $user['a_id'];
        $m_agent_level = new AgentLevelModel();
        $role_lang     = $m_agent_level->getRoleName();// 身份名称
        $data = [
            'team'   => count_team_orders_sales_total($family),// 团队业绩
            'me'     => count_team_orders_sales_total([$user['a_id']]),// 我的业绩
            'lower'  => count(model('Agents')->getSons($user['a_id'],$user['role'],4)),// 团队结构-下级(直属代理商未删除)
            'invite' => count(model('Agents')->getAgentAllSonsId(['inviter'=>$user['a_id'],'is_del'=>0,'is_use'=>1])),// 团队结构-推荐人数
            'member' => count(model('Agents')->getAgentAllSonsId(['inviter'=>$user['a_id'],'role'=>0,'is_del'=>0])),// 团队结构-会员
            'reward' => model('Agentprofit')->getRewardByDefined(['agent_id'=>$user['a_id']]),// 我的奖励
        ];
        $this->assign('role_lang',$role_lang);
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'首页','content'=>'','key'=>'','index'=>1]);

        //未审核的会员等级的名称
        $outsider=0;
        $this->assign('outsider',$outsider);

    	return $this->fetch('index');
    }

    /**
     * 登录页(done)
     */
    public function login()
    {
        $this->loginedRedirect();
        $this->assign('spider',['title'=>'登录','content'=>'','key'=>'']);
    	return $this->fetch('login');
    }

    /**
     * 保存登录信息(done)
     */
    public function doLogin()
    {
        $received = request();
        $phone = $received->param('phone');
        $password = $received->param('password');
        $m_agents = model('Agents');
        $info = $m_agents->field('agent_id as a_id,phone,password,nickname,end_etime,name,sex,stock_money,province,city,area,inviter,generation,role,wechat,status,head_img,is_use')->where(['phone'=>$phone,'is_del'=>0])->find();

        if($info)
        {
            if($info['password'] != md5(md5(trim($password))))
            {
                return ['error'=>['msg'=>'密码错误，请重新登录！']];
            }
            $session = [
                'a_id'       => $info['a_id'],
                'phone'      => $info['phone'],
                'nickname'   => $info['nickname'],
                'name'       => $info['name'],
                'sex'        => $info['sex'],
                'wechat'     => $info['wechat'],
                'stock'      => $info['stock_money'],// 库存金额
                'province'   => $info['province'],
                'city'       => $info['city'],
                'area'       => $info['area'],
                'inviter'    => $info['inviter'],// 邀请人ID
                'generation' => $info['generation'],// 代数
                'role'       => $info['role'],// 身份
                'endtime'    => $info['end_etime'],// 有效期(暂未启用)
                'status'     => $info['status'],// 申请状态
                'head_img'   => $info['head_img'],// 头像
                'is_use'     => $info['is_use'],// 是否已成为代理商
            ];

            //获取openid信息
            $weixinInfo=model('Weixinusers')->field('openid,agent_id')->where('agent_id='.$info['a_id'])->find();
            !empty($weixinInfo['openid']) && $session['openid']=$weixinInfo['openid'];

            session('user',$session);
            return ['msg'=>'登录成功！','url'=>url('Product/index')];
        }else{
            return ['error'=>['msg'=>'无此手机号，请注册！']];
        }
    }

    /**
     * 找回密码(done)
     */
    public function forget()
    {
        $this->loginedRedirect();
        $this->assign('spider',['title'=>'找回密码','content'=>'','key'=>'']);
        return $this->fetch('forget');
    }

    /**
     * 找回密码-获取手机验证短信(done)
     */
    public function getPhoneCode()
    {
        $received = request();
        $phone = $received->param('phone');
        if(isset($phone))
        {
            $is_registed = model('Agents')->where(['phone'=>$phone])->find();
            if($is_registed)
            {
                $code = get_mt_rand();// 验证码
                session($is_registed->agent_id.'_'.$phone,$code);// 保存到session
                $return = send_sms(['phone'=>$phone,'code'=>$code,'template'=>'SMS_86815037']);// 发送验证码
                if($return['Code'] == 'OK')
                {
                    return ['msg'=>'OK'];
                }else{
                    return ['error'=>['msg'=>'短信发送失败，请联系网站']];
                }
            }else{
                return ['error'=>['msg'=>'查无此账号，请输入注册时的手机账号']];
            }
        }else{
            return ['error'=>['msg'=>'手机号码不能为空']];
        }
    }

    /**
     * 找回密码-校验手机号及验证码(done)
     */
    public function checkCode()
    {
        $received = request();
        $phone = $received->param('tel');
        $code = $received->param('code');
        $is_registed = model('Agents')->where(['phone'=>$phone])->find();
        if($is_registed)
        {
            $session_code = session($is_registed->agent_id.'_'.$phone);
            if(trim($code) == trim($session_code))
            {
                session($is_registed->agent_id.'_'.$phone,null);// 清除验证码
                session('resetPWD',time()+600);// 设置有效时间
                session('RetrieveThePassword',['a_id'=>$is_registed->agent_id,'phone'=>$phone]);// 设置'闪存'信息
                return ['msg'=>'验证通过','url'=>url('Index/resetpwd')];
            }else{
                return ['error'=>['msg'=>'验证码错误']];
            }
        }else{
            return ['error'=>['msg'=>'请填写正确的注册号码']];
        }
    }

    /**
     * 找回密码-设置新密码(done)
     */
    public function resetpwd()
    {
        $this->loginedRedirect();
        $limit_time = session('resetPWD');
        if(isset($limit_time) && $limit_time >= time())
        {
            session('resetPWD',null);// 清除
            $this->assign('spider',['title'=>'找回密码','content'=>'','key'=>'']);
            return $this->fetch('resetpwd');
        }else{
            $this->redirect('Index/forget');
        }
    }

    /**
     * 找回密码-设置新密码(done)
     */
    public function setNewKey()
    {
        $received = request();
        $info = session('RetrieveThePassword');
        session('RetrieveThePassword',null);
        if(isset($info['phone']) && isset($info['a_id']))
        {
            $new_pwd = $received->param('pwd');
            $result = model('Agents')->save(['password'=>md5(md5(trim($new_pwd)))],['agent_id'=>$info['a_id'],'phone'=>$info['phone']]);
            if($result)
            {
                return ['msg'=>'修改成功','url'=>url('Index/login')];
            }else{
                return ['error'=>['msg'=>'修改失败']];
            }
        }else{
            return ['error'=>['msg'=>'未知错误']];
        }
    }

    /**
     * 注册(done)
     */
    public function register()
    {
        $received = request();
        $inviter_phone = $received->param('phone');
        $redirect_url = $received->param('redirect_url');//注册成功以后跳转商品详情页
    	//注册的时候获取 openid 存于session中，每次打开这个页面，重新初始化
    	$openid=Session::get('openid');
    	empty($openid) && $openid=$received->param('openid');


    	if(config('web.online')==1 && empty($openid)) {

	    	//$this->redirect('Index/Oauth/silentIndex',['phone'=>$inviter_phone]);// 静默方式
    		$this->redirect('Index/Oauth/getWechatCode',['phone'=>$inviter_phone]);//网页授权
	    	$openid=Session::get('openid');
	    	empty($openid) && $openid=$received->param('openid');

    	}

    	//校验此openid 是否已经注册  如果注册，那么跳转
    	if($openid && $inviter_phone) {
    		$weixin=model('Weixinusers');
    		$agent_id=0;
    		$agent_id=$weixin->getAgentIdByOpenId($openid);

    		if($agent_id){

    			$info=model('Agents')->find($agent_id);
    			$session = [
	    			'a_id'       => $info['agent_id'],
	    			'phone'      => $info['phone'],
	    			'nickname'   => $info['nickname'],
	    			'name'       => $info['name'],
	    			'sex'        => $info['sex'],
	    			'wechat'     => $info['wechat'],
	    			'stock'      => $info['stock_money'],// 库存金额
	    			'province'   => $info['province'],
	    			'city'       => $info['city'],
	    			'area'       => $info['area'],
	    			'inviter'    => $info['inviter'],// 邀请人ID
	    			'generation' => $info['generation'],// 代数
	    			'role'       => $info['role'],// 身份
	    			'endtime'    => $info['end_etime'],// 有效期(暂未启用)
	    			'status'     => $info['status'],// 申请状态
	    			'head_img'   => $info['head_img'],// 头像
	    			'is_use'     => $info['is_use'],// 是否已成为代理商
	    			'openid'     => $openid,
    			];
    			session('user','');
    			session('user',$session);
    			$this->loginedRedirect();
    		}
    	}


        $this->loginedRedirect();
        $this->assign('inviter',$inviter_phone);
        $this->assign('openid',$openid);
        $this->assign('redirect_url',$redirect_url);
        $this->assign('spider',['title'=>'注册','content'=>'','key'=>'']);
    	return $this->fetch('register');
    }

    /**
     * 保存注册信息(done)
     */
    public function saveRegister()
    {
        $m_agents = model('Agents');
        $received = request();
        $phone = $received->param('phone');
        $log = $m_agents->where(['phone'=>$phone,'is_del'=>0])->find();
        $openid=$received->param('openid');
        $redirect_url = $received->param('redirect_url');//注册保存信息以后跳回的链接

        if($log)
        {
            return ['error'=>['msg'=>'该手机号已注册']];
        }
        $add_data = [
            'phone' => $phone,
            'create_ctime' => date('Y-m-d H:i:s')
        ];
        $wechat = $received->param('wechat');
        $add_data['password'] = md5(md5(trim(substr($add_data['phone'],-6))));
        if(!empty($wechat))
        {// 存在邀请人手机号(2018-02-07:周一会议更改,由微信号改为手机号)
            $up_agent = $m_agents->field('agent_id,family')->where(['phone'=>$wechat])->find();// 邀请人族谱及ID
            if($up_agent)
            {
                $add_data['inviter']    = $up_agent->agent_id;
                $add_data['family']     = $up_agent->family.','.$up_agent->agent_id;
                $add_data['family']     = trim($add_data['family'],',');
                $add_data['generation'] = count(explode(',', $add_data['family']))+1;// 计算代数
            }
        }
        $add_result = $m_agents->save($add_data);// 添加注册信息

        //获取agent_id 然后更新到weixin_users表
        $agent_id=$m_agents->agent_id;
        empty($openid) && $openid=Session::get('openid');

        if($openid && $agent_id) {
        	$weixin=model('Weixinusers');
        	$info=$weixin->where("openid='".$openid."'")->find();
        	if(empty($info)){
        		$wdata=array();
        		$wdata['openid']=$openid;
        		$wdata['create_time']=time();
        		$wdata['agent_id']=$agent_id;
        		$weixin->save($wdata);
        	} else {
        		if(empty($info['agent_id'])) {
        			$adata=array();
        			$adata['agent_id']=$agent_id;
        			$weixin->update($adata,array('openid'=>$openid));
        		}
        	}
        }

        if($add_result)
        {
            /* 2018-09-13 CYL 注册后默认登录并返回产品列表页 */
            $info = $m_agents->where(['agent_id'=>$agent_id])->find();
            $session = [
                'a_id'       => $info['agent_id'],
                'phone'      => $info['phone'],
                'nickname'   => $info['nickname'],
                'name'       => $info['name'],
                'sex'        => $info['sex'],
                'wechat'     => $info['wechat'],
                'stock'      => $info['stock_money'],// 库存金额
                'province'   => $info['province'],
                'city'       => $info['city'],
                'area'       => $info['area'],
                'inviter'    => $info['inviter'],// 邀请人ID
                'generation' => $info['generation'],// 代数
                'role'       => $info['role'],// 身份
                'endtime'    => $info['end_etime'],// 有效期(暂未启用)
                'status'     => $info['status'],// 申请状态
                'head_img'   => $info['head_img'],// 头像
                'is_use'     => $info['is_use'],// 是否已成为代理商
                'openid'     => isset($info->wechat->openid) ? $info->wechat->openid : '',
            ];
            if(isset($openid) && !empty($openid))
            {
                $session['openid'] = $openid;
            }
            session('user',$session);
            $url_default = url('Product/index');
            if($redirect_url){//$redirect_url有值替换返回地址
                $url_default = url('Product/detail','id='.$redirect_url);
            }
            return ['msg'=>'注册成功','url'=>$url_default];
        }else{
            return ['error'=>['msg'=>'注册失败']];
        }
    }

    /**
     * 我的特权(done)
     */
    public function myPrivilege()
    {
        $this->checkLogin();
        $user = session('user');
        $info = model('Agents')->getAgentInfoByAid($user['a_id']);
        // 更新session
        $session = [
            'a_id'       => $user['a_id'],
            'phone'      => $info['phone'],
            'nickname'   => $info['nickname'],
            'name'       => $info['name'],
            'sex'        => $info['sex'],
            'wechat'     => $info['wechat'],
            'stock'      => $info['stock_money'],// 库存金额
            'province'   => $info['province'],
            'city'       => $info['city'],
            'area'       => $info['area'],
            'inviter'    => $info['inviter'],// 邀请人ID
            'generation' => $info['generation'],// 代数
            'role'       => $info['role'],// 身份
            'endtime'    => $info['end_etime'],// 有效期(暂未启用)
            'status'     => $info['status'],// 申请状态
            'head_img'   => $info['head_img'],// 头像
        ];
        session('user',$session);
        $refuse = 0;
        if($session['role'] >= 6)
        { // 最高级限制升级(由于等级后台可设置的不可确定性,暂时用最高级6)
            $refuse = 1;
        }
        $apply_log = model('AgentApplications')->where(['a_id'=>$user['a_id'],'is_del'=>0,'status'=>['in','1,2'],'type'=>['in','1,2']])->count();
        $this->assign('refuse',$refuse);
        $this->assign('apply_log',$apply_log);
        $this->assign('info',$info);
        $this->assign('spider',['title'=>'我的特权','content'=>'','key'=>'']);
        return $this->fetch('myPrivilege');
    }

    /**
     * 我的特权-注册申请(done)
     */
    public function applyRegist()
    {
        $this->checkLogin();
        $user = session('user');
        if($user['role'] >= 1)
        {// 已成为代理商的限制访问
            $this->redirect('Index/myPrivilege');
        }
        $received = request();
        $apply_id = $received->param('apply');// 申请目标等级ID
        $cache_n = $received->param('n');// 姓名
        $cache_w = $received->param('w');// 微信
        $cache_p = $received->param('p');// 省
        $cache_c = $received->param('c');// 市
        $cache_a = $received->param('a');// 区
        $cache_A = $received->param('A');// 地址
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        // $role_lang = ['普通会员','铜牌代理商','银牌代理商','金牌代理商','钻石代理商','皇冠代理商','分公司'];
        if($user['role'] >= $apply_id)
        {// 申请目标等级不得低于当前身份等级
            $apply_id = $user['role'] + 1;
        }
        $m_agents = model('Agents');
        $m_basice = model('BasicDataAddress');
        $data = $m_agents->getAgentInfoByAid($user['a_id']);
        $province = $data['province'];
        $city = $data['city'];
        $area = $data['area'];
        if(!empty($province))
        {
            $data['province'] = $m_basice->getCityNameById($province);
        }
        if(!empty($city))
        {
            $data['city'] = $m_basice->getCityNameById($city);
        }
        if(!empty($area))
        {
            $data['area'] = $m_basice->getCityNameById($area);
        }
        $data['name']     = $cache_n ? $cache_n : $data['name'];
        $data['wechat']   = $cache_w ? $cache_w : $data['wechat'];
        $data['province'] = $cache_p ? $cache_p : $data['province'];
        $data['city']     = $cache_c ? $cache_c : $data['city'];
        $data['area']     = $cache_a ? $cache_a : $data['area'];
        $data['address']  = $cache_A ? $cache_A : $data['address'];
        $this->assign('role_lang',$role_lang);
        $this->assign('apply',$apply_id);
        $this->assign('info',$user);
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'我的特权-申请成为代理商','content'=>'','key'=>'']);
        return $this->fetch('applyRegist');
    }

    /**
     * 我的特权-申请升级(done)
     */
    public function applyUpdate()
    {
        $this->checkLogin();
        $user = session('user');
        // 顶级身份不可访问
        if($user['role'] >= 6)
        {
            $this->redirect('Index/myPrivilege');
        }
        $data = model('Agents')->getAgentInfoByAid($user['a_id']);
        $received = request();
        $apply_id = $received->param('apply');// 申请目标等级ID
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        // $role_lang = ['普通会员','铜牌代理商','银牌代理商','金牌代理商','钻石代理商','皇冠代理商','分公司'];
        if($apply_id <= $data->role)
        {// 申请目标等级不得低于当前身份等级
            $apply_id = $data->role + 1;
        }
        $this->assign('role_lang',$role_lang);
        $this->assign('info',$data);
        $this->assign('apply',$apply_id);
        $this->assign('spider',['title'=>'我的特权-申请升级','content'=>'','key'=>'']);
        return $this->fetch('applyUpdate');
    }

    /**
     * 我的特权-选择升级等级页(done)
     */
    public function applyBox()
    {
        $this->checkLogin();
        $received = request();
        $type = $received->param('type');// 申请类型:1注册申请 2升级申请 3代申请
        $user = session('user');
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        $j = count($role_lang);
        for ($i=0; $i < $j; $i++)
        {
            if($i <= $user['role'])
            {// 低于当前用户等级的级别不显示
                unset($role_lang[$i]);
            }

            if(empty($role_lang[$i])){ unset($role_lang[$i]);}
        }

        $this->assign('type',$type);
        $this->assign('list',$role_lang);
        $this->assign('spider',['title'=>'我的特权-等级选择','content'=>'','key'=>'']);
        return $this->fetch('applyBox');
    }

    /**
     * 我的特权-处理注册申请信息(done)
     */
    public function saveRgApply()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'您还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $user = session('user');
        if(in_array($user['status'], [1,2]))
        {
            return ['error'=>['msg'=>'您有正在审核中的申请，不能重复提交','url'=>url('Index/myPrivilege')]];
        }
        $received = request();
        $m_agents = model('Agents');
        $m_basice = model('BasicDataAddress');
        $m_application = model('AgentApplications');
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        // $role_lang = ['会员','铜牌代理商','银牌代理商','金牌代理商','钻石代理商','皇冠代理商','分公司'];
        $province = $received->param('p');
        $city = $received->param('c');
        $area = $received->param('a');
        $agent_data = [
            'name'    => $received->param('name'),
            'wechat'  => $received->param('wechat'),
            'address' => $received->param('add'),
            'status'  => 1,// 申请注册
        ];
        if(!empty($province))
        {
            $agent_data['province'] = $m_basice->getCityIdByName($province);
        }
        if(!empty($city))
        {
            $agent_data['city'] = $m_basice->getCityIdByName($city);
        }
        if(!empty($area))
        {
            $agent_data['area'] = $m_basice->getCityIdByName($area);
        }
        $agent_address_isset = model('AgentAddress')->where(['a_id'=>$user['a_id'],'is_del'=>0])->count();
        if($agent_address_isset <= 0)
        {
            $address['a_id']       = $user['a_id'];
            $address['name']       = $agent_data['name'];
            $address['phone']      = $user['phone'];
            $address['province']   = $province;
            $address['city']       = $city;
            $address['area']       = $area;
            $address['address']    = $agent_data['address'];
            $address['is_default'] = 1;
            model('AgentAddress')->insert($address);
        }
        $info_result = $m_agents->save($agent_data,['agent_id'=>$user['a_id']]);// 保存代理商信息
        $apply_data = [
            'target' => $received->param('apply'),
            'create_ctime' => date('Y-m-d H:i:s'),
            'a_id' => $user['a_id'],
            'type' => 1,
            'remarks' => '备注信息:代理商申请，申请成为 '.$role_lang[$received->param('apply')],
            'now' => $user['role'],// 2018-07-11 CYL 添加记录当前角色
            'money' => $received->param('money'),// 支付金额
            'img' => $received->param('img'),
        ];
        $apply_result = $m_application->save($apply_data);// 保存申请信息
        if($info_result && $apply_result)
        {
            session('user.status',1);// 修改代理商申请状态
            return ['msg'=>'提交成功','url'=>url('Index/myPrivilege')];
        }else{
            return ['error'=>['msg'=>'提交失败']];
        }
    }

    /**
     * 我的特权-处理升级申请信息(done)
     */
    public function saveApply()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'您还没有登录，请登录后操作','url'=>url('Index/login')]];
        }
        $user = session('user');
        if(in_array($user['status'], [1,2]))
        {
            return ['error'=>['msg'=>'您有正在审核中的申请，不能重复提交','url'=>url('Index/myPrivilege')]];
        }
        $m_agents = model('Agents');
        $m_application = model('AgentApplications');
        $received = request();
        $target = $received->param('apply');
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        // $role_lang = ['会员','铜牌代理商','银牌代理商','金牌代理商','钻石代理商','皇冠代理商','分公司'];
        $highest = 7;// 启用的最高身份等级
        if($target > $user['role'] && $target <= $highest)
        {// 申请目标等级必须在当前用户等级之上并且不大于最高限制等级
            $add_data = [
                'a_id'         => $user['a_id'],
                'type'         => 2,
                'target'       => $target,
                'create_ctime' => date('Y-m-d H:i:s'),
                'remarks'      => '备注信息:升级申请，申请成为 '.$role_lang[$target],
                'now'          => $user['role'],// 2018-07-11 CYL 添加记录当前角色
                'money'        => $received->param('money'),
                'img'          => $received->param('img'),
            ];// 申请信息
            $add_result = $m_application->save($add_data);
            if($add_result)
            {
                $m_agents->save(['status'=>2],['agent_id'=>$user['a_id']]);// 修改用户状态
                session('user.status',2);// 修改代理商申请状态
                return ['msg'=>'提交成功','url'=>url('myPrivilege')];
            }else{
                return ['error'=>['msg'=>'提交失败']];
            }
        }else{
            return ['error'=>['msg'=>'提交错误']];
        }
    }

    /**
     * 交易单上传
     */
    public function uploadImg()
    {
        $file = request()->file('screen_img');
        if(!empty($file))
        {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                $filePath = '/uploads/'.$info->getSaveName();
                if (!empty($filePath))
                {
                    return json_encode(['img'=>$filePath],JSON_UNESCAPED_SLASHES);
                }else{
                    return json(['error'=>['msg'=>'失败']]);
                }
            }else{
                json([$file->getError()]);
            }
        }else{
            return json(['error'=>['msg'=>'失败']]);
        }
    }

    /**
     * 我的特权-审核记录
     */
    public function applyLog()
    {
        $this->checkLogin();
        $user = session('user');
        $log  = model('AgentApplications')->getApplies(['a_id'=>$user['a_id'],'status'=>['in','1,2'],'type'=>['in','1,2']]);
        $this->assign('data',$log);
        $this->assign('spider',['title'=>'审核记录','content'=>'','key'=>'']);
        return $this->fetch('applyLog');
    }

    /**
     * (代)下级申报
     */
    public function declareApply()
    {
        $this->checkLogin();
        $user = session('user');
        $received = request();
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        $apply_id       = $received->param('apply','');// 申请目标等级ID
        $cache['phone'] = $received->param('p','');
        $cache['name']  = $received->param('n','');
        $cache['money'] = $received->param('m','');
        if($cache['phone'])
        {
            $agent = model('agents')->where(['inviter'=>$user['a_id'],'is_del'=>0,'phone'=>$cache['phone']])->find();
            if($agent)
            {
                $cache['aid']  = $agent['agent_id'];
                $cache['role'] = $agent['role'];
                $apply_id = empty($apply_id) ? $agent['role'] + 1 : $apply_id;
            }
        }
        $apply_id = empty($apply_id) ? 1 : $apply_id;// 默认最低级代理商
        $this->assign('role_lang',$role_lang);
        $this->assign('apply',$apply_id);
        $this->assign('cache',$cache);
        $this->assign('spider',['title'=>'下级申报']);
        return $this->fetch('declareApply');
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
        $sons = model('agents')->getAgentAllSonsId(['inviter'=>session('user.a_id'),'role'=>['lt',session('user.role')],'is_del'=>0]);
        if(empty($a_id)){
            return ['error'=>['msg'=>'该手机号未注册，请先去注册']];
        }else if(empty($sons) || !in_array($a_id['agent_id'],$sons)){
            return ['error'=>['msg'=>'该用户不是您的直推下级，请重新申报']];
        }
        if($a_id['role'] == session('user.role'))
        {
            return ['error'=>['msg'=>'该用户身份与您的身份相同，不能申报']];
        }
        $a_id['role'] += 1;
        $a_id['role_lang'] = $role_lang[$a_id['role']+1];
        return $a_id;
    }

    /* 保存申报信息 */
    public function saveDeclare()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'您还没有登录，请登录后操作']];
        }
        $user = session('user');
        $received  = request();
        $phone     = $received->param('phone');
        $target    = $received->param('apply');
        $name      = $received->param('name');
        $money     = $received->param('money');
        $money     = abs($money);

        $agentInfo = model('agents')->where(['phone'=>$phone,'is_del'=>0,'inviter'=>$user['a_id']])->find();// 被申报人ID
        $stock_val = model('agents')->where(['agent_id'=>$user['a_id']])->find();// 申报人当前库存

        if(!$agentInfo)
        {
            return ['error'=>['msg'=>'该用户不是您的直推下级，不能申报']];
        }else if($target <= $agentInfo['role']){
            return ['error'=>['msg'=>'申报身份必须高于当前身份，请重新选择']];
        }else if($money > $stock_val['stock_money']){
            return ['error'=>['msg'=>'您当前库存值不足，请先充值']];
        }else if($money > 9999999.9){
            return ['error'=>['msg'=>'支付金额输入错误，请重新输入']];
        }

        if($agentInfo && ($agentInfo['status'] == 1 || $agentInfo['status'] == 2))
        {
            return ['error'=>['msg'=>'该用户当前有正在审核的申请，不能申报']];
        }
        $m_agent_level = new AgentLevelModel();
        $role_lang = $m_agent_level->getRoleName(0);// 身份名称
        $data = [];
        $data['create_ctime'] = date('Y-m-d H:i:s');
        $data['apply_by_id']  = $user['a_id'];
        $data['type']         = 6;// 申报信息
        $data['target']       = $target;
        $data['a_id']         = $agentInfo['agent_id'];
        $data['money']        = $money;
        $data['img']          = $received->param('img');
        $data['now']          = $agentInfo['role'];
        $data['now_g']        = $agentInfo['generation'];
        $data['remarks']      = '备注信息:申报申请，申请成为 '.$role_lang[$target];
        $result = model('AgentApplications')->save($data);
        if(false === $result)
        {
            return ['error'=>['msg'=>'操作失败']];
        }else{
            $modify = [];
            if($agentInfo['name'] != $name && !empty($name))
            {
                $modify['name'] = $name;
            }
            $modify['status'] = $agentInfo['role'] == 0 ? 1 : 2;
            model('agents')->where(['agent_id'=>$agentInfo['agent_id']])->update($modify);// 修改名称
            return ['msg'=>'操作成功','url'=>url('Index/myPrivilege')];
        }
    }

    /* 目标等级 */
    public function declareApplyBox()
    {
        $this->checkLogin();
        $user = session('user');
        $received = request();
        $m_agent_level = new AgentLevelModel();
        $role_lang     = $m_agent_level->getRoleName(0);// 身份名称
        $phone = $received->param('p','');
        $low_level = 1;// 最低可选身份
        if(!empty($phone))
        {
            $agent = model('agents')->where(['phone'=>$phone,'is_del'=>0])->find();
            if($agent)
            {
                $low_level = $agent['role'] + 1;// 当前身份的上一级为最低可选身份
            }
        }
        $j = count($role_lang);
        for ($i=0; $i < $j; $i++)
        {
            if($i < $low_level)
            {// 低于可选身份的去掉
                unset($role_lang[$i]);
            }
            if($i > $user['role'])
            {// 高于当前用户等级的级别不显示
                unset($role_lang[$i]);
            }
            if(empty($role_lang[$i])){ unset($role_lang[$i]); }
        }
        unset($role_lang[0]);
        $this->assign('list',$role_lang);
        $this->assign('spider',['title'=>'等级选择','content'=>'','key'=>'']);
        return $this->fetch('declareApplyBox');
    }

    /**
     * 申报记录
     */
    public function declareLog()
    {
        $this->checkLogin();
        $user = session('user');
        $log  = model('AgentApplications')->getAppliesWithInfo(['p.apply_by_id'=>$user['a_id'],'p.status'=>['in','0,1,2'],'p.type'=>6]);
        $this->assign('list',$log);
        $this->assign('spider',['title'=>'申报记录']);
        return $this->fetch('declareLog');
    }

    /**
     * 我的业绩(done)
     */
    public function myAchievement()
    {
        $this->checkLogin();
        $user = session('user');
        $first_day = date('Y-m-01');// 当月第一天
        $now       = date('Y-m-d');// 当前时间
        $days      = date('t');// 当月天数
        $team_total = model('Agents')->where(['family'=>['like','%'.$user['a_id'].'%'],'role'=>['lt',$user['role']],'is_del'=>0])->count();// 团队总数
        $add_day = model('Agents')->where(['family'=>['like','%'.$user['a_id'].'%'],'role'=>['lt',$user['role']],'is_del'=>0,'create_ctime'=>['between',[$now.' 00:00:00',date('Y-m-d H:i:s')]]])->count();// 日增人数(查询时间当天新增人数)
        $sale_total      = model('Agentorderreward')->getSaleTotalByAgentId($user['a_id']);// 销售累计总额
        $order_total     = model('Agentorderreward')->countSaleOrderTotalByAgentId($user['a_id']);// 订单累计总数
        $sale_arg_month  = model('Agentorderreward')->getSaleTotalByAgentId($user['a_id'],[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月销售额
        $order_arg_month = model('Agentorderreward')->countSaleOrderTotalByAgentId($user['a_id'],[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月订单数
        $sale_day        = model('Agentorderreward')->getSaleTotalByAgentId($user['a_id'],[strtotime($now),strtotime($now.' + 1 day')]);// 日销售额(查询时间当天的销售额)
        $order_day       = model('Agentorderreward')->countSaleOrderTotalByAgentId($user['a_id'],[strtotime($now),strtotime($now.' + 1 day')]);// 日订单数(查询时间当天的订单数)
        $data = [
            'team_total'      => $team_total,// 团队总数
            'add_day'         => $add_day,// 日增人数
            'sale_total'      => $sale_total,// 销售累计总额
            'order_total'     => $order_total,// 订单累计总数
            'sale_day'        => $sale_day,// 日销售额
            'order_day'       => $order_day,// 日订单数
            'sale_arg_month'  => $sale_arg_month,// 本月销售额
            'order_arg_month' => $order_arg_month,// 本月订单数
        ];
        $sale_day_percent  = $sale_total == 0 ? 0 : round($data['sale_day']/$data['sale_total'],2);// 日销售额占比
        $order_day_percent = $order_total == 0 ? 0 : round($data['order_day']/$data['order_total'],2);// 日销售订单占比
        $add_day_percent   = $team_total == 0 ? 0 : round($data['add_day']/$data['team_total'],2);// 日增人数占比
        if($sale_total == 0)
        {
            $sale_percent = [];
        }else{
            if ($sale_total == $sale_day || $sale_day == 0)
            {
                $sale_percent = [
                    ['item'=>$sale_day == 0 ? '销售总额' : '日销售额','count'=>1]
                ];// 销售比例图
            } else {
                $sale_percent = [
                    ['item'=>'销售总额','count'=>1-$sale_day_percent],
                    ['item'=>'日销售额','count'=>$sale_day_percent]
                ];// 销售比例图
            }
        }
        if($order_total == 0)
        {
            $order_percent = [];
        }else{
            if ($order_total == $order_day || $order_day == 0)
            {
                $order_percent = [
                    ['item'=>$order_day == 0 ? '订单总数': '日订单数','count'=>1]
                ];
            } else {
                $order_percent = [
                    ['item'=>'订单总数','count'=>1-$order_day_percent],
                    ['item'=>'日订单数','count'=>$order_day_percent]
                ];// 订单比例图
            }
        }
        if($team_total == 0)
        {
            $team_percent = [];
        }else{
            if ($add_day == 0 || $add_day == $team_total)
            {
                $team_percent = [
                    ['item'=>$add_day == 0 ? '团队总数' : '日增人数','count'=>1]
                ];// 订单比例图
            } else {
                $team_percent = [
                    ['item'=>'团队总数','count'=>1-$add_day_percent],
                    ['item'=>'日增人数','count'=>$add_day_percent]
                ];// 团队比例图
            }
        }
        $this->assign('data',$data);
        $this->assign('sale_percent',json_encode($sale_percent));
        $this->assign('order_percent',json_encode($order_percent));
        $this->assign('team_percent',json_encode($team_percent));
        $this->assign('spider',['title'=>'我的业绩','content'=>'','key'=>'']);
        return $this->fetch('myAchievement');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $this->checkLogin();
        session('user', null);
        $this->redirect('Index/login');
    }

    /**
     * 团队业绩
     */
    public function teamPerformance()
    {
        //获取当前用户
        $user = session('user');
        //1.首先获取当前用户下的全部直属代理的agent_id
        //操作代理商表
        $Agents=model('Agents');
        //操作订单表
        $Orders=model('Orders');
        //首先查询登录用户的直属下属
        $userList = model('Agents')->getSons($user['a_id'],$user['role'],4);//获取直属代理的信息

        $uidLowAry=array();
        foreach($userList as $k=>$v)
        {
            $uidLowAry[]=$v;

        }
        $uidLowAry[]=$user['a_id'];

        $uid = join(',',$uidLowAry);

        $first_day = date('Y-m-01');// 当月第一天
        $now       = date('Y-m-d');// 当前时间
        $days      = date('t');// 当月天数
        //销售总额和订单总数
        $total=array();
        $total['money_total']=count_team_orders_sales_total($uidLowAry);
        $total['order_total']=model('Agentorderreward')->countSaleOrderTotalByAgentId($uid);

        //日销售总额和日订单总数
        $day_total=array();
        $day_total['day_money_total']=count_team_orders_sales_total($uidLowAry,[strtotime($now),strtotime($now.' + 1 day')]);
        $day_total['day_order_total']=model('Agentorderreward')->countSaleOrderTotalByAgentId($uid,[strtotime($now),strtotime($now.' + 1 day')]);

         //月销售总额和月订单总数
        $month_total=array();
        $month_total['month_money_total'] = count_team_orders_sales_total($uidLowAry,[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月销售额
        $month_total['month_order_total'] = model('Agentorderreward')->countSaleOrderTotalByAgentId($uid,[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月订单数


        $total_percent_money = empty($month_total['month_money_total']) ? 0 : round($day_total['day_money_total']/$total['money_total']*100,2);
        $total_percent_order = empty($month_total['month_order_total']) ? 0 : round($day_total['day_order_total']/$total['order_total']*100,2);

        $money_residue = 100-$total_percent_money;
        $order_residue = 100-$total_percent_order;

        $this->assign('money_residue',$money_residue);
        $this->assign('order_residue',$order_residue);
        $this->assign('total_percent_money',$total_percent_money);//销售百分比
        $this->assign('total_percent_order',$total_percent_order);//订单百分比

        $this->assign('month_total',$month_total);//月销售总额和月订单总数
        $this->assign('day_total',$day_total);//日销售总额和日订单总额
        $this->assign('total',$total);//团队销售总额和订单总数
        $this->assign('spider',['title'=>'团队业绩','content'=>'','key'=>'']);
        return $this->fetch('teamPerformance');
    }

    public function privilegeTeamPerformance()
    {
        //获取当前用户
        $user = session('user');
        //1.首先获取当前用户下的全部直属代理的agent_id
        //操作代理商表
        $Agents=model('Agents');
        //操作订单表
        $Orders=model('Orders');
        //首先查询登录用户的直属下属
        $userList = model('Agents')->getSons($user['a_id'],$user['role'],4);//获取直属代理的信息

        $uidLowAry=array();
        foreach($userList as $k=>$v)
        {
            $uidLowAry[]=$v;

        }
        $uidLowAry[]=$user['a_id'];

        $first_day = date('Y-m-01');// 当月第一天
        $now       = date('Y-m-d');// 当前时间
        $days      = date('t');// 当月天数
        $userinfo=array();
        foreach($uidLowAry as $k=>&$val)
        {
            $tmpInfo=$Agents->find($val);

            $userinfo[$k]['agent_id']=$tmpInfo['agent_id'];
            $userinfo[$k]['head_img']=$tmpInfo['head_img'];
            $userinfo[$k]['generation']=$tmpInfo['generation'];
            $userinfo[$k]['role']=$tmpInfo['role'];
            $userinfo[$k]['nickname']=$tmpInfo['nickname'];
            $userinfo[$k]['wechat']=$tmpInfo['wechat'];
            //日
            $userinfo[$k]['day_money_total']=count_team_orders_sales_total([$tmpInfo['agent_id']],[strtotime($now),strtotime($now.' + 1 day')]);
            $userinfo[$k]['day_order_total']=model('Agentorderreward')->countSaleOrderTotalByAgentId($tmpInfo['agent_id'],[strtotime($now),strtotime($now.' + 1 day')]);

            //月
            $userinfo[$k]['month_money_total']  = count_team_orders_sales_total([$tmpInfo['agent_id']],[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月销售额
            $userinfo[$k]['month_order_total'] = model('Agentorderreward')->countSaleOrderTotalByAgentId($tmpInfo['agent_id'],[strtotime($first_day),strtotime($first_day.' + 1 month')]);// 本月订单数

        }

        $this->assign('userinfo',$userinfo);
        $this->assign('spider',['title'=>'团队业绩','content'=>'','key'=>'']);
        return $this->fetch('privilegeTeamPerformance');
    }

}