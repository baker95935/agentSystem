<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\admin\model\Agentorders as adminOrdersModel;
use app\admin\model\Agentorderreward as adminRewardModel;
use app\admin\model\Agentorderconsigneeaddress as adminConsigneeAddressModel;

class Product extends Controller
{
    protected $beforeActionList = [
        'first' => ['only'=>'index'],
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
     * 产品列表
     */
    public function index()
    {
        // 校验下 如果不是手机端打开，那么跳转到AMS后台
        if(!isMobile()) {
            $this->redirect('admin/Index/index');
        }
        /*if(!session('?user'))
        {
            $this->redirect('Index/login');
        }*/
        $received = request();
        $category = $received->param('cid');// 分类
        $sort     = $received->param('sort');// 排序类型
        $search   = $received->param('search');// 搜索词
        $m_product = model('Products');
        
        $role_tip=0;//等级不一样的时候提醒
        //用户身份校验
        if(session('?user')){
        	$user=session('user');
        	
        	//校验登录状态和真实的等级信息
        	$agent=model('Agents');
        	if(!empty($user)) {
        		$agentInfo=$agent->find($user['a_id']);
        		  $agentInfo['role']!=$user['role'] && $role_tip=1;
        	}
        }
        
        $where = ['state'=>1];// 查询条件
        if(isset($category))
        {
            /* CYL-改为包含子级分类 */
            $son_cate_id   = model('ProductCategory')->getAllSonsCateArrByID($category);
            $son_cate_id[] = (int)$category;
            $where['category_id'] = ['in',implode(',',$son_cate_id)];
        }
        if(!empty($search))
        {
            $where['product_name'] = ['like','%'.$search.'%'];
        }
        
        switch ($sort)
        {
            case 1:// 价格正序
                $order = 'sales_price ASC';
                break;
            case 2:// 价格倒序
                $order = 'sales_price DESC';
                break;
            case 3:// 销量正序
                $order = 'sales_volume ASC';
                break;
            case 4:// 销量倒序
                $order = 'sales_volume DESC';
                break;
            default:// 默认
                $order = 'create_time DESC';// 排序条件
                $sort = 0;
                break;
        }
        $list = $m_product->field('id,product_name,category_id,classify_id,product_img,explain,sales_price,unit,inventory,sales_volume,create_time,is_first_order,false_volume,is_agent_level')->where($where)->order($order)->select();
        //循环取出销量值sales_volume为实际销量，false_volume为后台设置销量
        foreach ($list as $k=>&$value){
            $value['mix_volume'] = $value['false_volume']+$value['sales_volume'];
            
            //只显示比登录用户等级高的商品
            if(isset($user)) {
            	if($value['is_agent_level'] >0 && $user['role']>=$value['is_agent_level']) {
            		unset($list[$k]);
            	}
            }
            
            //临时使用
            if($value['id']==32) {
            	if(isset($user) && $user['role']!=0 ) {
            		unset($list[$k]);
            	}
            }
        }

        $this->assign('role_tip',$role_tip);
        $this->assign('list',$list);
        $this->assign('sort',$sort);
        $this->assign('search',$search);
        $this->assign('cid',$category);
        $this->assign('spider',['title'=>'商品列表','content'=>'','key'=>'','index'=>2]);
        return $this->fetch('index');
    }

    /**
     * 分类页 2018-04-23
     */
    public function category()
    {
        if(!session('?user'))
        {
            $this->redirect('Index/login');
        }
        $received = request();
        $from    = $received->param('from','');// 访问来源
        $current = $received->param('cid');// 当前选中的上级类目
        $top_category = model('ProductCategory')->getGivenCateSonsByID(0);// 所有顶级
        $current = isset($current) ? $current : (count($top_category) > 0 ? $top_category[0]['id'] : 0);
        $right_cate = model('ProductCategory')->getGivenCateSonsByID($current);
        $map_array  = model('ProductCategory')->getParentFamilyByID($current);
        array_push($map_array,(int)$current);// 加入当前类目
        sort($map_array);// 重新排序
        $map = model('ProductCategory')->categoryMapsArr($map_array);
        $this->assign('current_map',$map);
        $this->assign('top_category',$top_category);
        $this->assign('right_cate',$right_cate);// 右侧
        $this->assign('from',$from);
        $this->assign('spider',['title'=>'产品分类','content'=>'','key'=>'']);
        return $this->fetch('category');
    }

    /**
     * 产品详情页(done)
     */
    public function detail()
    {
        /*if(!session('?user'))
        {
            $this->redirect('Index/login');
        }*/
        $received = request();
        $pid = $received->param('id');// 商品ID
        $agentID = $received->param('agentID');// 微店列表页微店代理商ID
        $openid = $received->param('openid');//微店列表扫描人OPENID

        Session::set('openid',$openid);
        $Weixinuser  = model('Weixinusers');//微信用户表
        $Agents  = model('Agents');//代理商表
        $phone=0;

        if (!empty($agentID)){
            $phone = $Agents->where('agent_id='.$agentID)->value('phone');
        }
        $count = 1;// 设置默认值
        if (!empty($openid)){
            $count=$Weixinuser->where(['openid'=>$openid,'agent_id'=>['gt',0]])->count();
        }

        if($phone && $count == 0){
        	$this->redirect('index/index/register',['phone'=>$phone,'redirect_url'=>$pid]);
        }
        $user=session('user');
        
        
        if(isset($pid))
        {
            // 产品详情
            $m_product = model('Products');
            $info = $m_product->getOnsaleProductInfoById($pid);

            //校验下 是否首单并且已购买的产品
            $can_buy=1;
            if($user)
            {
	            $pcount=$m_product->getFirstOrderProductCountById($pid,$user['a_id']);
	            $pcount>0 && $can_buy=0;
            }

            //校验下，限购是否已经购买
            $limit_buy=0;
            if($info['is_Purchase_a']==1) {
            	$limit_buy=1;
            	$pcount=$m_product->getLimitProductCountById($pid,$user['a_id']);
            	$pcount==0 && $limit_buy=0;
            }

            //产品标签
            $label = '';
            $this->assign('can_buy',$can_buy);
            $this->assign('limit_buy',$limit_buy);
            $this->assign('info',$info);
            $this->assign('spider',['title'=>'产品详情-'.$info->product_name,'content'=>'','key'=>'']);

            //宣传图片的数量
            $imgCount=0;
            $imgCount = model('ProductImagesData')->getProductImgsCountById($pid);// 多图
            $this->assign('imgCount',$imgCount);

            //校验真实的等级信息和商品信息
            $role_tip=0;
            $agent=model('Agents');
            if(!empty($user)) {
            	$agentInfo=$agent->find($user['a_id']);
            	if($info['is_agent_level']>0) {
            		$agentInfo['role']>=$info['is_agent_level'] && $role_tip=1;
            	}
            }
            $this->assign('role_tip',$role_tip);
            
            return $this->fetch('detail');
        }else{
            $this->redirect('Product/index');
        }
    }

    /**
     * 产品分享资料页(done)
     */
    public function share()
    {
        if(!session('?user'))
        {
            $this->redirect('Index/login');
        }
        $received = request();
        $pid = $received->param('id');// 商品ID
        if(isset($pid))
        {
            $m_product = model('Products');
            $info = $m_product->getOnsaleProductInfoById($pid,'explain,product_name');
            $imgs = model('ProductImagesData')->getProductImgsById($pid);// 多图
            $this->assign('info',$info->explain);
            $this->assign('imgs',$imgs);
            $this->assign('spider',['title'=>'产品资料-'.$info->product_name,'content'=>'','key'=>'']);
            return $this->fetch('share');
        }else{
            $this->redirect('Product/index');
        }
    }

    /**
     * 加入购物车(done)
     */
    public function addToCart()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后再操作']];
        }
        $user = session('user');
        $received = request();
        $pid = $received->param('pid');
        $num = $received->param('num');
        if(empty($pid) || empty($num))
        {
            return ['error'=>['msg'=>'请选择正确数量的产品']];
        }
        $isset = model('Cart')->where(['a_id'=>$user['a_id'],'pid'=>$pid])->find();
        if ($isset)
        {
        	$prodcut=model('Products');
        	$productInfo=$prodcut->find($isset->pid);
        	if($productInfo['is_Purchase_a']==1) {
        		return ['error'=>['msg'=>'限购产品只能添加一个到购物车']];
        	}
            $num = $num + $isset->num;
            $result = model('Cart')->save(['num'=>$num],['a_id'=>$user['a_id'],'pid'=>$pid]);// 更新
        } else {
            $cart_data = [
                'a_id' => $user['a_id'],
                'pid' => $pid,
                'num' => $num,
                'create_time' => time(),
            ];
            $result = model('Cart')->insert($cart_data);// 添加
        }
        if($result)
        {
            return ['msg'=>'添加成功'];
        }else{
            return ['error'=>['msg'=>'添加失败']];
        }
    }

    /**
     * 购物车(done)
     */
    public function myCart()
    {
        if(!session('?user'))
        {
            $this->redirect('Index/login');
        }else{
            $user = session('user');
            
            $role_tip=0;
            $agent=model('Agents');
            if(!empty($user)) {
            	$agentInfo=$agent->find($user['a_id']);
            	$agentInfo['role']!=$user['role'] && $role_tip=1;
            }
            
            $list = model('Products')->alias('p')->field('p.product_name,p.sales_price,p.product_img,p.inventory,c.id AS cid,c.pid,c.num,is_Purchase_a')->join('agent_cart c','p.id=c.pid')->where(['c.a_id'=>$user['a_id']])->select();
            $this->assign('list',$list);
            $this->assign('role_tip',$role_tip);
            $this->assign('spider',['title'=>'我的购物车','content'=>'','key'=>'']);
            if (empty($list))
            {
                return $this->fetch('cartEmpty');
            }
            return $this->fetch('myCart');
        }
    }

    /**
     * 购物车-变更产品数量(done)
     */
    public function modCartGoods()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后再操作']];
        }
        $user = session('user');
        $received = request();
        $pid = $received->param('pid');
        $num = $received->param('num');
        if(empty($pid) || empty($num))
        {
            return ['error'=>['msg'=>'请选择正确数量的产品']];
        }
        $result = model('Cart')->save(['num'=>$num],['a_id'=>$user['a_id'],'pid'=>$pid]);// 更新
        if($result)
        {
            return ['msg'=>'操作成功'];
        }else{
            return ['error'=>['msg'=>'操作失败']];
        }
    }

    /**
     * 购物车-删除(done)
     */
    public function delCartGoods()
    {
        if(!session('?user'))
        {
            return ['error'=>['msg'=>'你还没有登录，请登录后再操作']];
        }
        $user = session('user');
        $received = request();
        $cid = $received->param('cid');
        if (isset($cid))
        {
            $isset = model('Cart')->where(['a_id'=>$user['a_id'],'id'=>$cid])->find();
            if ($isset)
            {
                $result = model('Cart')->where(['a_id'=>$user['a_id'],'id'=>$cid])->delete();
                if ($result)
                {
                    return ['msg'=>'操作成功'];
                } else {
                    return ['error'=>['msg'=>'操作失败']];
                }
            } else {
                return ['error'=>['msg'=>'该商品不在购物车或已删除']];
            }
        } else {
            return ['error'=>['msg'=>'操作错误，请刷新后重试']];
        }
    }

    /**
     * 下单前确认页
     */
    public function preOrder()
    {
        if(!session('?user'))
        {
            $this->redirect('Index/login');
        }else{
            $user = session('user');

            $received = request();
            $cids = $received->param('cids');// 商品ID字符串 来源:购物车
            $pid = $received->param('id');// 商品ID 来源:直接购买
            $num = $received->param('num');// 商品数量 来源:直接购买
            $rid = $received->param('rid');// 选择的收货地址 来源:地址管理页

            $productIdStr = $productNumStr = $productType = '';
            $trans_expenses = $product_total_price = $agent_total_price= 0;// 运费  商品总额   代理折扣
            $productInfo=array();//理解购买的商品信息
            // 选购商品信息
            if (isset($pid,$num) && !empty($pid) && !empty($num))
            {// 立即购买
                $list = model('Products')->field('product_name,sales_price,product_img,weight,express')->where(['id'=>$pid])->find();
                $data[0] = [
                    'product_name' => $list->product_name,
                    'sales_price' => $list->sales_price,
                    'product_img' => $list->product_img,
                    'num' => $num,
                    'pid' => $pid,
                ];
                // 2018-07-18 CYL 运费计算所需数据
                $express = [['express'=>$list->express,'price'=>get_agent_discount_price_by_role($pid,$num,$user['role']),'weight'=>$list->weight,'num'=>$num]];

                //商品总额
                $product_total_price=round($num*$list->sales_price,2);
                //代理折扣
                $agent_total_price=get_agent_decrease_price_by_role($pid,$num,$user['role']);
                //总价
                $total = get_agent_discount_price_by_role($pid,$num,$user['role']);

                //获取商品信息
                $productIdStr=$pid;
                $productNumStr=$num;
                $productType=1;
                
                $productInfo=model('Products')->getOnsaleProductInfoById($pid);

                /*
                //校验是否已经购买过，购买过直接跳转
                $count=model('Products')->getFirstOrderProductCountById($pid,$user['a_id']);

                if($count>0) {
                	$this->redirect('Index/Product/detail',['id'=>$pid]);
                }

                //校验是否限购商品，并且已经买过
                $limitCount = model('Products')->getLimitProductCountById($pid,$user['a_id']);
                if($limitCount>0) {
                	$this->redirect('Index/Product/detail',['id'=>$pid]);
                }*/

            } else if(isset($cids) && !empty($cids)) {// 购物车确定
                $list = model('Products')->alias('p')->field('p.product_name,p.sales_price,p.product_img,p.weight,p.express,c.id AS cid,c.pid,c.num')->join('agent_cart c','p.id=c.pid')->where(['c.a_id'=>$user['a_id'],'c.id'=>['in',$cids]])->select();

                if(empty($list))
                {
                    $this->redirect(url('Product/myCart'));
                }

                $total = 0;
                $i=0;
                foreach ($list as $value)
                {
                    $cache['product_name'] = $value['product_name'];
                    $cache['sales_price'] = $value['sales_price'];
                    $cache['product_img'] = $value['product_img'];
                    $cache['num'] = $value['num'];
                    $cache['pid'] = $value['pid'];
                    $data[] = $cache;

                    //商品总额
                    $product_total_price += round($value['sales_price']*$value['num'],2);
                    //代理折扣
                    $agent_total_price += get_agent_decrease_price_by_role($value['pid'],$value['num'],$user['role']);

                    //总价
                    $total += get_agent_discount_price_by_role($value['pid'],$value['num'],$user['role']);

                    $i++;
                    //获取商品信息
                    $productIdStr=$value['pid'].','.$productIdStr;
                    $productNumStr=$value['num'].",".$productNumStr;
                    $productType=$i;

                    /*
                    //校验是否已经购买过，购买过直接跳转
                    $count = model('Products')->getFirstOrderProductCountById($value['pid'],$user['a_id']);
                    if($count>0) {
                    	$this->redirect('Index/Product/detail',['id'=>$pid]);
                    }

                    //校验是否限购商品，并且已经买过
                    $limitCount = model('Products')->getLimitProductCountById($value['pid'],$user['a_id']);
                    if($limitCount>0 || $cache['num']>1) {
                    	$this->redirect('Index/Product/detail',['id'=>$pid]);
                    }*/


                    // 2018-07-18 CYL 运费计算所需数据
                    $express[] = ['express'=>$value->express,'price'=>get_agent_discount_price_by_role($value['pid'],$value['num'],$user['role']),'weight'=>$value->weight,'num'=>$value->num];
                }
            }else{
                $this->redirect('Index/index');
            }
            //默认地址(无默认选择第一个)
            $user_address = model('AgentAddress')->getDefault($user['a_id']);
            // 收货地址
            if (isset($rid))
            {
                $address_isset = model('AgentAddress')->where(['id'=>$rid,'is_del'=>0])->find();
                if($address_isset)
                {
                    $user_address = $address_isset;
                }
            }
            // 2018-07-19 CYL 计算运费
            $trans_expenses = countOrderExpress(['a_id'=>$user['a_id'],'aid'=>$user_address['id'],'goods'=>$express]);

            // 2018-07-20 CYL 检查收货地址是否完整
            $addressIsComplete = false;
            if($user_address)
            {
                $addressIsComplete = check_address_is_completed($user_address->toArray());
            }
            $this->assign('addressIsComplete',$addressIsComplete);

            $this->assign('address',$user_address);
            $this->assign('productIdStr',$productIdStr);
            $this->assign('productNumStr',$productNumStr);
            $this->assign('productType',$productType);
            $this->assign('total',$total);// 总额
            $this->assign('user',$user);

            //运费  商品总额  折扣
            $this->assign('trans_expenses',$trans_expenses);
            $this->assign('product_total_price',$product_total_price);
            $this->assign('agent_total_price',$agent_total_price);

            $orderTotal = $total;
            if($trans_expenses>0) {
            	$orderTotal = round($total+$trans_expenses,2);
            }
            $this->assign('orderTotal',$orderTotal);// 总额

            //校验下  如果库存够，那么显示库存支付
            $stockPay=0;
            $agent=model('index/Agents');
            $agentInfo=$agent->find($user['a_id']);

            $agentInfo['stock_money']>=$orderTotal && $stockPay=1;
            $this->assign('stockPay',$stockPay);//库存支付

            $this->assign('list',$data);
            $this->assign('productInfo',$productInfo);//单一商品信息  适用于立即购买
            
            $this->assign('spider',['title'=>'填写订单','content'=>'','key'=>'']);
            return $this->fetch('preOrder');
        }
    }

    /**
     * 立即购买
     */
    public function buyNow()
    {
        //思路整理
        /*
         * 先生成订单号
         * 然后插入到订单表
         ** 减产品库存并增加销量 CYL **
         ** 清除购物车表该商品记录 CYL **
         * 然后插入到收货人地址表
         * 发货人信息等发货的时候处理
         * 由于公司发货和卖家发货的奖励机制处理不同，订单提交的时候不在处理奖励
         */
        if (!session('?user'))
        {
            $this->redirect('Index/login');
        }
        $user = session('user');
        $received = request();

        $orders = model('Orders');

        if($received->method() == 'POST')
        {
        	//获取参数
            $addressId     = $received->param('addressId');
            $productIdStr  = $received->param('productIdStr');
            $productNumStr = $received->param('productNumStr');
            $productType   = $received->param('productType');
            $paystyle      = $received->param('paystyleVal');
            $remark        = $received->param('remark');

         
            //校验是否限购商品是否已经购买过
            $count=model('Products')->getLimitProductCountById($productIdStr,$user['a_id']);
            if($count>0) {
            	$this->redirect('Index/Product/detail',['id'=>$productIdStr]);
            }

           
            if(!empty($addressId) && !empty($productIdStr) && !empty($productNumStr) && !empty($productType)) {
          		$order_number=$orders->addOrder($user['a_id'],$addressId,$productIdStr,$productNumStr,$productType,$paystyle,$remark,0);
            }
 
      
            if($order_number>0) {
            	$orderInfo=$orders->field('order_amount_pay,paystyle')->where("order_number='".$order_number."'")->find();
            	//小于支付限额，并且选择的微信支付,并且有openid的

            	//在获取一次OPENID
            	$openid='';
            	isset($user['openid']) && $openid=$user['openid'];
            	if(!isset($user['openid'])) {
            		$weixin=model('Weixinusers');
            		$openid=$weixin->getUserOpenid($user['a_id']);
            	}

            	$agent=model('index/Agents');
            	$agentInfo=$agent->find($user['a_id']);

            	//如果是线下支付，那么给可能获取收益的人 发个提醒
            	if($orderInfo['paystyle']==1) {
            		send_weixin_message_by_order_number($order_number);
            	}

            	if($orderInfo['order_amount_pay']<=config('web.pay_limit') && $orderInfo['paystyle']==2 && $openid) {
            		//跳转到支付页面
            		$this->redirect('Pay/wechat',"order_number=".$order_number);
            	}else if($orderInfo['paystyle']==3 && $agentInfo['stock_money']>=$orderInfo['order_amount_pay']) {
            		//跳转到库存支付页面
            		$this->redirect('Pay/stock',"order_number=".$order_number);
            	} else {

            		$this->redirect('Order/myOrders');
            	}
            }
        }else{
            $this->redirect('Product/index');
        }
    }

}