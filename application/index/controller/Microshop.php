<?php
namespace app\Index\controller;

use think\Controller;
use think\Request;
use think\Session;
class  Microshop extends Controller
{

    protected $beforeActionList = [
        // 'first' => ['only' => 'personalinfolist,personalinfo'],
    ];

    protected function first()
    {
        $user = session('user');
        $tip = '';
        if ($user['role'] <= 0) {
            $tip = 1;
        }
        $this->assign('tip', $tip);
    }

    /**
     * 未登录的跳转登录页
     */
    protected function checkLogin()
    {
        if (!session('?user'))
        {// 未登录
            $this->redirect('Index/login');
        }
    }

    /**
     * 店铺列表
     */
    public function index($search = '')
    {
        $m_shop = model('Shop');
        $where  = [];
        if(session('?user'))
        {
            $where['a_id'] = ['neq',session('user.a_id')];
        }
        if($search)
        {
            $awhere = ['nickname|shop_name' => ['like','%'.$search.'%'],'is_del'=>0];
            $list = $m_shop->hasWhere('agent',$awhere)->whereor(['shop_name'=>['like','%'.$search.'%']])->where($where)->select();
        }else{
            if(session('?user'))
            {
                $list = $m_shop->hasWhere('agent',['is_del'=>0,'agent_id'=>['neq',session('user.a_id')]])->select();
            }else{
                $list = $m_shop->hasWhere('agent',['is_del'=>0])->select();
            }
        }
        if($list)
        {
            foreach ($list as $key => $val)
            {
                $count = $val->goods()->where(['type'=>1,'is_del'=>0])->count();
                if($count > 0)
                {
                    $val['count'] = $count;
                }else{
                    unset($list[$key]);
                }
            }
        }
        $this->assign('spider',['title'=>'店铺','index'=>3]);
        $this->assign('list',$list);
        $this->assign('search',$search);
        return $this->fetch('index');
    }

    /* 我的店 */
    public function microshop()
    {
        $this->checkLogin();
        $user = session('user');
        $m_shop = model('Shop');
        $info = $m_shop->where(['a_id'=>$user['a_id']])->find();
        if(!$info)
        {
            $data['a_id']   = $user['a_id'];
            $data['shop_name'] = empty($user['nickname']) ? $user['phone'] : $user['nickname'];
            $data['qrcode'] = '';// 店铺二维码
            $data['fans']   = 0;
            $data['pv']     = 0;
            $data['sale']   = 0;
            $data['orders'] = 0;
            // 添加记录
            $m_shop->save($data);
            $info = $data;
        }
        $info['onsale'] = model('Shopgoods')->getCount($user['a_id']);

        //打开微店生成一张二维码
        if(empty($info['qrcode'])){
            $id=$user['a_id'];
            $pv = 1;

            $path = "uploads/shopcode/";// 保存路径
            if(!file_exists($path))
            {
                mkdir($path, 0700);
            }
            $filename =$id.'.png';// 文件名

            $save_file = $path.$filename;// 保存:png第二个参数

            if(!file_exists($save_file)) {
                vendor('phpqrcode.phpqrcode');
                $QRcode = new \QRcode();
                $data = url('/Index/Microshop/common_shop/pv', $pv . '/id=' . $id, 'html', true);// 二维码保存的信息
                $level = 'M';// 纠错级别：L、M、Q、H
                $point_size = 7;// 点的大小：1到10,用于手机端4就可以了
                $size = 2;// 空白大小
                ob_end_clean();//清空缓冲区
                $QRcode->png($data, $save_file, $level, $point_size, $size);
            }

            $m_shop->where('a_id',$user['a_id'])->update(['qrcode'=>$save_file]);
            $info['qrcode']=$save_file;
        }

        $this->assign('info',$info);
        $this->assign('spider',['title'=>'我的店','content'=>'','key'=>'']);
        return $this->fetch('microshop/microshopinfo');
    }

    /**
     * 店铺商品-(done)
     */
    public function shopGoods()
    {
        $this->checkLogin();
        $user = session('user');
        $m_shopgoods = model('Shopgoods');
        $data['onsale'] = $m_shopgoods->getCount($user['a_id']);
        $data['unsale'] = $m_shopgoods->getCount($user['a_id'],2);
        $this->assign('data',$data);
        $this->assign('spider',['title'=>'店铺商品']);
        return $this->fetch('shopgoods');
    }

    /**
     * 商品列表:1商城商品 2上架商品 3库存商品
     */
    public function selectGoods($type = 1)
    {
        $this->checkLogin();
        $user = session('user');
        $received = request();
        if(!in_array($type, [1,2,3]))
        {
            $this->redirect('shopGoods');
        }
        $search_name = $received->param('search','');// 检索
        $order_sort  = $received->param('order','');// 排序
        $category_id = $received->param('cid','');// 分类
        $m_shopgoods = model('Shopgoods');
        $m_products  = model('Products');
        switch ($order_sort)
        {
            case 'su':// 销量正序
                $order = 'sale_num';
                break;
            case 'sd':// 销量倒序
                $order = 'sale_num DESC';
                break;
            case 'pu':// 价格正序
                $order = 'sales_price';
                break;
            case 'pd':// 价格倒序
                $order = 'sales_price DESC';
                break;
            default:
                $order = 'create_time';
                break;
        }
        $where = ['sg.is_del'=>0,'sg.a_id'=>$user['a_id']];
        if($search_name)
        {
            $where['p.product_name'] = ['like','%'.$search_name.'%'];
        }
        switch ($type)
        {
            case 2:
                $where['sg.type'] = 1;
                !empty($category_id) && $where['p.category_id'] = $category_id;
                $list = $m_shopgoods->getListJoinProduct($where,$order);
                break;
            case 3:
                $where['sg.type'] = 2;
                !empty($category_id) && $where['p.category_id'] = $category_id;
                $list = $m_shopgoods->getListJoinProduct($where,$order);
                break;
            case 1:
            default:
                $where = ['state'=>1];// 覆盖$where
                !empty($search_name) && $where['product_name'] = ['like','%'.$search_name.'%'];
                !empty($category_id) && $where['category_id'] = $category_id;
                $list = $m_products->field('*,(sales_volume+false_volume) AS sale_num')->where($where)->order($order)->select();// 全部商品
                $onsale = $m_shopgoods->where(['a_id'=>$user['a_id'],'is_del'=>0,'type'=>1])->column('p_id');
                $this->assign('onsale',$onsale);
                break;
        }
        $title = [1=>'商城商品',2=>'上架商品',3=>'库存商品'];
        $this->assign('spider',['title'=>$title[$type]]);
        $this->assign('search',$search_name);
        $this->assign('order_sort',$order_sort);
        $this->assign('cid',$category_id);
        $this->assign('type',$type);
        $this->assign('list',$list);
        return $this->fetch('selectgoods');
    }

    /**
     * 店铺商品操作(done):1入库 2入库并上架
     */
    public function addGoods()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'您还没有登录，请登录后操作']];
        }
        $user     = session('user');
        $received = request();
        $type     = $received->param('type','');
        $g_ids    = $received->param('val','');// 商品ID
        $m_shopgoods = model('Shopgoods');
        if(empty($type) || !in_array($type,[1,2]) || empty($g_ids))
        {
            return ['error'=>['msg'=>'参数错误，请重新提交']];
        }
        $g_ids = explode(',', $g_ids);
        if(empty($g_ids))
        {
            return ['error'=>['msg'=>'参数错误，请重新提交']];
        }
        $log = $m_shopgoods->where(['a_id'=>$user['a_id']])->select();// 获取已添加到商品
        if(!$log)
        {// 没有记录
            $data = [];
            switch ($type)
            {
                case '1':
                    foreach ($g_ids as $key => $val)
                    {
                        $cache = [];
                        $cache['a_id'] = $user['a_id'];
                        $cache['p_id'] = $val;
                        $cache['type'] = 2;
                        $cache['create_ctime'] = date('Y-m-d H:i:s');
                        $data[] = $cache;
                    }
                    break;
                case '2':
                    foreach ($g_ids as $key => $val)
                    {
                        $cache = [];
                        $cache['a_id'] = $user['a_id'];
                        $cache['p_id'] = $val;
                        $cache['type'] = 1;
                        $cache['create_ctime'] = date('Y-m-d H:i:s');
                        $data[] = $cache;
                    }
                    break;
                default:
                    return ['error'=>['msg'=>'操作错误']];
                    break;
            }
            $result = $m_shopgoods->saveAll($data);
            if(false !== $result)
            {
                return ['msg'=>'操作成功'];
            }else{
                return ['error'=>['msg'=>'操作失败']];
            }
        }
        $log_del_pid = $log_up_pid = $log_down_pid = [];
        foreach ($log as $k => $v)
        {
            if($v['is_del'] == 1)
            {
                $log_del_pid[] = $v['p_id'];// 删除的商品ID
            }else{
                if($v['type'] == 1)
                {
                    $log_up_pid[] = $v['p_id'];// 上架中的商品ID
                }else{
                    $log_down_pid[] = $v['p_id'];// 下架中的商品ID
                }
            }
        }
        $result_add = $result_down = $result_up = $result_del = 0;
        // 已删除的商品再次添加
        $mod_del_pid  = array_intersect($g_ids, $log_del_pid);
        if($mod_del_pid)
        {
            $result_del = $m_shopgoods->where(['a_id'=>$user['a_id'],'p_id'=>['in',implode(',', $mod_del_pid)]])->update(['is_del'=>0,'type'=>$type==2?1:2]);
        }
        // 已上架的商品再次添加
        $mod_up_pid   = array_intersect($g_ids,$log_up_pid);
        if($mod_up_pid)
        {
            $result_up = $m_shopgoods->where(['a_id'=>$user['a_id'],'p_id'=>['in',implode(',', $mod_up_pid)]])->update(['type'=>$type==2?1:2]);
        }
        // 已下架的商品再次添加
        $mod_down_pid = array_intersect($g_ids,$log_down_pid);
        if($mod_down_pid)
        {
            $result_down = $m_shopgoods->where(['a_id'=>$user['a_id'],'p_id'=>['in',implode(',', $mod_down_pid)]])->update(['type'=>$type==2?1:2]);
        }
        // 没有记录的商品首次添加
        $add_pid = array_diff($g_ids,$mod_del_pid,$mod_up_pid,$mod_down_pid);
        if($add_pid)
        {
            $add_data = [];
            foreach ($add_pid as $add_k => $add_v)
            {
                $add_cache = [];
                $add_cache['a_id'] = $user['a_id'];
                $add_cache['p_id'] = $add_v;
                $add_cache['type'] = $type==2 ? 1 : 2;
                $add_cache['create_ctime'] = date('Y-m-d H:i:s');
                $add_data[] = $add_cache;
            }
            $result_add = $m_shopgoods->saveAll($add_data);
        }
        if(false !== $result_add || false !== $result_down || false !== $result_up || false !== $result_del)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 店铺商品操作(done): 1上架 2下架 3删除
     */
    public function modStatus()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'您还没有登录，请登录后操作']];
        }
        $user     = session('user');
        $received = request();
        $type     = $received->param('type','');
        $gids     = $received->param('val','');// 店铺商品表ID
        $m_shopgoods = model('Shopgoods');
        $m_products  = model('Products');
        if(empty($type) || !in_array($type,[1,2,3]) || empty($gids))
        {
            return ['error'=>['msg'=>'参数错误，请提交有效数据']];
        }
        $gids = explode(',', $gids);
        switch ($type)
        {
            case 2:// 下架
                $result = $m_shopgoods->where(['id'=>['in',implode(',',$gids)],'a_id'=>$user['a_id'],'is_del'=>0])->update(['type'=>2]);
                break;
            case 3:// 删除
                $result = $m_shopgoods->where(['id'=>['in',$gids],'a_id'=>$user['a_id'],'is_del'=>0])->update(['is_del'=>1]);
                break;
            case 1:// 上架
            default:
                $result = $m_shopgoods->where(['id'=>['in',implode(',',$gids)],'a_id'=>$user['a_id'],'is_del'=>0])->update(['type'=>1]);
                break;
        }
        if(false !== $result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    //店铺名称
    public function shopName()
    {
        $user = session('user');
        $shop  = model('Shop');//操作我的店铺表
        $date=$shop::get(['a_id'=>$user['a_id']]);

        $this->assign('date',$date);
        $this->assign('spider',['title'=>'店名','content'=>'','key'=>'']);
        return $this->fetch('microshop/shopName');
    }

    //添加店铺名称
    public function save()
    {
        $received = request();
        $shop  = model('Shop');//操作我的店铺表
        $user    = session('user');
        $info    = $shop->where('a_id',$user['a_id'])->find();//获取店铺表代理商信息
        $datamsg = array();
        $datamsg['shop_name']       = $info['shop_name'];
        //更新店铺名称
        $shopName=$received->param('shopName');
        if(!empty($shopName)){
            if ($shopName ==  $datamsg['shop_name']){
                return ['msg'=>'更新成功','url'=>url('Microshop/microshop')];
            }else{
                $result = $shop->where('a_id',$user['a_id'])->update(['shop_name'=>$shopName]);
                if($result)
                {
                    return ['msg'=>'更新成功','url'=>url('Microshop/microshop')];
                }else{
                    return ['error'=>['msg'=>'更新失败']];
                }
            }
        }
    }
    /*微店背景图*/
    public function shopBackground()
    {
        $shop  = model('Shop');//操作我的店铺表
        $user = session('user');//获取本地用户信息
        $shoplist = $shop->where('a_id',$user['a_id'])->find();
        $this->assign('shoplist',$shoplist);

        $this->assign('spider',['title'=>'微店背景','content'=>'','key'=>'']);

        return $this->fetch('microshop/shopBackground');
    }
    //上传微店背景图
    public function uploads()
    {

        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('up_img');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!empty($file)){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');

            if($info){
                $filePath = '/uploads/'.$info->getSaveName();
                $imgp= str_replace("\\","/",$filePath);//转译路径里面的反斜杠
                if (!empty($imgp)){
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

    /*某人的店铺首页*/
    public function myshop()
    {
        $user     = session('user');//获取当前用户

        $shoplist  = model('Shopgoods');//操作我的店铺表
        $shop  = model('Shop');//操作我的店铺表
        $date = $shop->where('a_id',$user['a_id'])->find();


        $data=array();
        $data['a_id']=$user['a_id'];
        $data['type']= 1;//类型1上架 2下架。这里只需要上架商品
        $data['is_del']= 0;
        $info = $shoplist->where($data)->select();

        $this->assign('info',$info);
        $this->assign('date',$date);

        return $this->fetch('microshop/myshop');
    }

    /**
     *
     * 此方法是用户通过二维码扫面进来的页面
     * 然后添加访问数量
     * 下面链接是扫完后的二维码链接
     * http://www.agentsystem.cn/index/microshop/common_shop/pv/1/id/100074.html
     */
    public function common_shop()
    {

        $received = request();
        $pv = $received->param('pv');
        $id = $received->param('id');

        $openid   = $received->param('openid');
        $shoplist = model('Shopgoods');//操作我的店铺表
        $shop     = model('Shop');//操作我的店铺表
        $date = $shop->where('a_id='.$id)->find();

        $Weixinuser = model('Weixinusers');
        $agent      = model('Agents');
        if(!session('?user'))
        {
            if(empty($openid)){
                $appid=config('wechat.appid');
                $redirect_uri=urlencode(config('web.domain')."index/Microshop/getUserCode/pv/".$pv.'/id/'.$id);
                $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
                $this->redirect($url);
            } else {
            	$count=$Weixinuser->where("openid='".$openid."'")->count();
            	if($count>0) {
            		$agent_id=$Weixinuser->where("openid='".$openid."'")->value('agent_id');

            		if($agent_id) {
    	        		$agentInfo=$agent->find($agent_id);
    	        		// 更新session
    	        		$session = [
    		        		'a_id'       => $agentInfo['agent_id'],
    		        		'phone'      => $agentInfo['phone'],
    		        		'nickname'   => $agentInfo['nickname'],
    		        		'name'       => $agentInfo['name'],
    		        		'sex'        => $agentInfo['sex'],
    		        		'wechat'     => $agentInfo['wechat'],
    		        		'stock'      => $agentInfo['stock_money'],// 库存金额
    		        		'province'   => $agentInfo['province'],
    		        		'city'       => $agentInfo['city'],
    		        		'area'       => $agentInfo['area'],
    		        		'inviter'    => $agentInfo['inviter'],// 邀请人ID
    		        		'generation' => $agentInfo['generation'],// 代数
    		        		'role'       => $agentInfo['role'],// 身份
    		        		'endtime'    => $agentInfo['end_etime'],// 有效期(暂未启用)
    		        		'status'     => $agentInfo['status'],// 申请状态
    		        		'head_img'   => $agentInfo['head_img'],// 头像
    		        		'openid'     =>$openid,// 头像
    	        		];
    	        		session('user',$session);
            		}
            	}
            }
        }else{
            $userInfo = $Weixinuser->where(['agent_id'=>session('user.a_id')])->find();// 获取openID
            $openid   = $userInfo['openid'];
        }

        if (!empty($pv)){
            if(session('?user') && $id == session('user.a_id'))
            {
                /* 自己访问自己的店铺不增加pv */
            }else{
                $shop->where('a_id='.$id)->setInc('pv');//数据库访问量该字段加1
            }
        }

        $data=array();
        $data['a_id']=$id;
        $data['type']= 1;//类型1上架 2下架。这里只需要上架商品
        $data['is_del'] = 0;
        $info = $shoplist->where($data)->select();
        $agentID = $id;
        $this->assign('agentID',$agentID);
        $this->assign('openid',$openid);
        $this->assign('info',$info);
        $this->assign('date',$date);

        return $this->fetch('microshop/common_shop');
    }
    //获取code，通过code获取网页授权
    public function getUserCode()
    {
        $received = request();

        $appid=config('wechat.appid');
        $secret=config('wechat.secret');

        $code = $received->param('code');
        $pv = $received->param('pv');
        $id = $received->param('id');

        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=$secret&code=".$code."&grant_type=authorization_code";
        $info=file_get_contents($url);
        $data=json_decode($info,true);

        //微信会根据CODE值，会返回给你相对access_token！
        $access_token=$data['access_token'];
        $openid=$data['openid'];

        //获取微信用户信息
        $user_url="https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $user=file_get_contents($user_url);
        $userinfo=json_decode($user,true);


        $Weixinuser=model('Weixinusers');
        $count=$Weixinuser->where("openid='".$openid."'")->count();
        if($count==0) {
        	$data=array();
            $data['openid']=$userinfo['openid'];
            $data['nickname']=$userinfo['nickname'];
            $data['headimgurl']=$userinfo['headimgurl'];
            $data['city']=$userinfo['city'];
            $data['province']=$userinfo['province'];
            $data['sex']=$userinfo['sex'];
            $data['create_time']=time();

            $users=model('Weixinusers');
            $users->save($data);
        }


        $this->redirect('Index/Microshop/common_shop',['id'=>$id,'pv'=>$pv,'openid'=>$openid]);
    }
    //背景图保存方法
    public function save_background()
    {
        $received = request();
        $shop  = model('Shop');//操作我的店铺表
        $user = session('user');

        $imgurl = $received->param('imgurl');

        if (!empty($imgurl)){
           $info =  $shop->where('a_id',$user['a_id'])->update(['background'=>$imgurl]);
            if (false !==$info){
                $data['state']= 1;
                $data['savedir']=url('/Index/Microshop/microshop');
            }else{
                $data['state'] = 0;
            }
            return $data;
        }else{
                $data['state'] = 0;
                return ($data);
        }

    }

}