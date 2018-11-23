<?php
use think\Db;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//后台系统管理相关的状态中文字符1是是
function get_admin_status($status=1)
{
	$res='';
	$status==1?$res='是':$res='否';
	return $res;
}

//代理商直接销售奖励 和用户的等级和销售额有关，和公司发货有关 type=1写入数据库 type=2 直接返回数组
//20180713 此函数废弃，代理直销奖励废弃 by zhaoxueming
function agent_reward_direct_sale($orderNumber,$type=1)
{
	$res=0;
	$finalResult=array();

	//根据订单号 获取订单信息
	$orders=model('Agentorders');
	$orderInfo=$orders->where("order_number='".$orderNumber."'")->find();

	//先获取代理商的等级
	$agent=model('Agents');
	$userInfo=$agent->find($orderInfo['agent_id']);

	//然后获取直属上级的等级
	$upUserInfo=array();
	if($userInfo['inviter']) {
		$upUserInfo=$agent->find($userInfo['inviter']);
	}


	//如果没有直属上级，直接返回
	if(empty($upUserInfo)) {
		return $res;
	}

	//代理折扣奖励是否启用
	$config=model('Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();

	//然后根据等级，查找上级等级是否启用
	$level=model('Agentlevel');
	$levelInfo=$level->find($upUserInfo['role']);

	//如果启用那么商品里面的，寻找对应的上级代理商的奖励系数
	if($levelInfo['valid']==1 && $configInfo['valid_agency_reward']==1 && $upUserInfo['role']>$userInfo['role'])
	{
		$reward=model('Productagentreward');
		$systemReward=model('Agentrewardagency');
		$orderReward=model('Agentorderreward');

		//引入库存模型
		$stock=model('Agentstockchange');

		$productList=$orders->where("order_number='".$orderNumber."'")->select();//获取订单的商品列表

		foreach($productList as $k=>$v)
		{
			$rewardInfo=$reward->where(array('role'=>$upUserInfo['role'],'product_id'=>$v['pid']))->find();

			//查找下，系统中设置的奖励系数
			$systemRewardInfo=$systemReward->where(array('role'=>$upUserInfo['role']))->find();

			//找到了商品中对应的系数，那么直接乘以
			if(!empty($rewardInfo) && $rewardInfo['agent_reward'])
			{
				$finalStock=round($v['ptotal_price_pay']*(100-$rewardInfo['agent_reward'])/100,2);
				$finalReward=round($v['ptotal_price_pay']*$rewardInfo['agent_reward']/100,2);
				$finalSalesAmount=round($v['ptotal_price_pay']*$rewardInfo['agent_reward']/100,2)+$finalStock;//收入等于奖励+减去的库存

			} else {

				//如果没有奖励 直接返回
				if(empty($systemRewardInfo['agent_reward'])) {
					return $res;
				}

				//用系统的进行计算
				$finalStock=round($v['ptotal_price_pay']*(100-$systemRewardInfo['agent_reward'])/100,2);
				$finalReward=round($v['ptotal_price_pay']*$systemRewardInfo['ratio']/100,2);
				$finalSalesAmount=round($v['ptotal_price_pay']*$systemRewardInfo['ratio']/100,2)+$finalStock;//收入等于奖励+减去的库存
			}

			//获取最低库存额度
			$lowest_limit=$systemRewardInfo['lowest_limit'];

			//判断是否已经存过了，如果有记录是update
			$count=$orderReward->where("order_number='".$orderNumber."' and product_id=".$v['pid'].' and reward_type=2')->count();

			if($count==0)
			{
				$canDo=0;
				//判断下库存够不够减
				$upUserInfo['stock_money']-$finalStock>=$lowest_limit && $canDo=1;

				//库存不足 发个消息提醒
				if($type==1 && $canDo==0) {
					message_send_stock_not_enough($upUserInfo['agent_id']);
				}

				if($canDo>0 && $finalReward>0) {

					//写库存变动记录
					$ndata=array();
					$ndata['agent_id']=$upUserInfo['agent_id'];
					$ndata['create_time']=time();
					$ndata['change_before']=$upUserInfo['stock_money'];
					$ndata['money']=$finalStock;
					$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
					$ndata['change_type']=3;//减库存
					$ndata['account_type']=5;//其他

					$ndata['audit_time']=time();//审核时间
					$ndata['status']=2;//已审核
					$ndata['order_number']=$orderInfo['order_number'];
					$ndata['remark']='代理直销奖励减库存';

					if($type==1) {

						//tp的BUG 第二次开始要手工指定主键ID
						isset($stock->id) && $ndata['id']=$stock->id+1;
						/*同一个实例多次添加，需要设置isupdate*/
						$res=$stock->isUpdate(false)->save($ndata);

						//写入变动记录成功，减库存
						if($res>0) {
							$result=$agent->where('agent_id',$upUserInfo['agent_id'])->setDec('stock_money',$finalStock);
						}
					}

					//存到库里,订单记录表
					$data=array();
					$data['agent_id']=$userInfo['inviter'];
					$data['create_time']=time();
					$data['directsale_reward']=$finalReward;
					$data['sales_amount']=$finalSalesAmount;
					$data['wholesale_reward']=0;
					$data['recommend_reward']=0;
					$data['reward_type']=2;
					$data['product_id']=$v['pid'];
					$data['status']=1;
					$data['order_number']=$orderNumber;
					$data['remark']='代理直销奖励';

					if($type==1 && $data['directsale_reward']>0) {
						//tp的BUG 第二次开始要手工指定主键ID
						isset($orderReward->id) && $data['id']=$orderReward->id+1;
						/*同一个实例多次添加，需要设置isupdate*/
						$res=$orderReward->isUpdate(false)->save($data);
					}

					$type==2 && $finalResult[]=$data;


				}
			}

		}

		//如果是2，那么返回数组
		if($type==2){
			return $finalResult;
		}
	}


	return $res;

}

//代理商间接销售 type=1写入数据库 type=2返回数组
//20180917只有收益不减库存
function agent_reward_whole_sale($orderNumber,$type=1)
{
	/*
	 * 根据用户当前等级，寻找上级中比自己级别高的用户，然后乘以奖励系数的级差，减逐级上级的库存，
	 *
	 */
	$finalUserAry=$finalResult=array();

	$agent=model('admin/Agents');

	//根据订单号 获取订单信息
	$orders=model('admin/Agentorders');
	$orderInfo=$orders->where("order_number='".$orderNumber."'")->find();


	//根据订单号获取产品列表
	$productList=$orders->where("order_number='".$orderNumber."'")->select();

	//订单奖励表模型
	$orderReward=model('admin/Agentorderreward');

	//引入库存模型
	$stock=model('admin/Agentstockchange');


	Db::startTrans();//开启事务

	try{

		//根据产品ID，由于产品可能设置了奖励，所以循环产品
		foreach($productList as $k=>$v)
		{
			//先获取可以分得奖励的用户信息
			$finalUserAry=get_agent_agency_reward_user($orderNumber,$v['pid']);

			//那么 获取奖励的用户ID，那么循环获取奖励吧,理论上最多5次
			foreach($finalUserAry as $kk=>$vv)
			{

				//加到奖励表，等结算就到收入里面，如果减失败，则不处理
				if(!isset($vv['ratioDiff']) && $type==1) continue;

				//如果减完之后 大于当前等级最低的库存额度，那么可以执行
				if(($vv['ratioDiff']>=0) || $type==2)
				{
					//获取变更之前的库存额
					$agentInfo=$agent->find($vv['agent_id']);

					//校验下是否已经写记录
					$count=$orderReward->where("order_number='".$orderInfo['order_number']."' and agent_id=".$vv['agent_id'].' and product_id='.$v['pid'].' and reward_type=3')->count();

					if($count==0)
					{
					 
						//写收益
						$data=array();
						$data['create_time']=time();
						$data['agent_id']=$vv['agent_id'];
						$data['product_id']=$v['pid'];
						$data['order_number']=$orderInfo['order_number'];
						$data['wholesale_reward']=get_agent_agency_reward($v['ptotal_price_pay'],$vv['ratioDiff'],$vv['minusStock'],1);
						$data['sales_amount']=get_agent_agency_reward($v['ptotal_price_pay'],$vv['ratioDiff'],$vv['minusStock'],2);
						$data['directsale_reward']=0;
						$data['recommend_reward']=0;
						$data['reward_type']=3;
						$data['status']=1;
						$data['remark']='代理间接销售收益';

						if($type==1) {
							//tp的BUG 第二次开始要手工指定主键ID
							isset($orderReward->id) && $data['id']=$orderReward->id+1;
							/*同一个实例多次添加，需要设置isupdate*/
							$orderReward->isUpdate(false)->save($data);

						}

						$type==2 && $finalResult[]=$data;
					}
				}
			}
		}

		//执行事务
		Db::commit();
	} catch (\Exception $e) {

	    //回滚事务
	    Db::rollback();
	}

	if($type==2) {
		return $finalResult;
	}

}




//代理招商奖励 type=1 写入库  2返回结果
function agent_reward_recommend($orderNumber,$type=1)
{
	/*
	 * 根据代理的ID，寻找上级ID，然后根据的等级，去寻找开启了几级，然后根据系数，计算收益
	 */
	$res=0;
	$finalResult=array();

	//根据订单号 获取订单信息
	$orders=model('Agentorders');
	$orderInfo=$orders->where("order_number='".$orderNumber."'")->find();


	//根据订单号获取产品列表
	$productList=$orders->where("order_number='".$orderNumber."'")->select();


	//先获取用户当前的信息
	$agent=model('Agents');
	$userInfo=$agent->find($orderInfo['agent_id']);

	//如果是意向代理，那么招商收益使用他上级的等级获取深度
	if($userInfo['role']==0 && !empty($userInfo['inviter'])) {
		$tmp=$agent->find($userInfo['inviter']);
		$userInfo['role']=$tmp['role'];
	}

	//根据用户的等级，获取招商奖励的深度层级
	$levelInfo=model('Agentlevel')->find($userInfo['role']);

	//招商奖励是否启用
	$config=model('Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();

	//判断是否开启招商奖励，并且是否有上级，如果可以继续走
	if(!empty($userInfo['inviter']) && $configInfo['valid_recommend_reward']==1 && $levelInfo['deep']>0){

		//订单奖励表模型
		$orderReward=model('Agentorderreward');

		//订单循环，获取多种产品
		foreach($productList as $k=>$v)
		{

			//获取所有上级
			$parentIdStr=$userInfo['family'];
			if(!empty($parentIdStr))
			{
				//提取深度个数到数组
				$parentIdAry=explode(',',$parentIdStr);
				$parentIdAry=array_reverse($parentIdAry);//反转
				$realAry=array_slice($parentIdAry,0,$levelInfo['deep']);//从后往前取数据

				//循环查询招商奖励收益，然后存到奖励表
				foreach($realAry as $kk=>$vv)
				{

					$data=array();
					$tmpUserInfo=$agent->find($vv);
					$data['directsale_reward']=0;
					$data['wholesale_reward']=0;
					$data['recommend_reward']=get_agent_recommend_reward($v['pid'],$v['ptotal_price_pay'],$tmpUserInfo['role'],$kk+1);
					$data['recommend_hierarchy']=$kk+1;
					$data['sales_amount']=0;
					$data['create_time']=time();
					$data['agent_id']=$tmpUserInfo['agent_id'];
					$data['order_number']=$orderNumber;
					$data['product_id']=$v['pid'];
					$data['reward_type']=1;
					$data['remark']='代理招商奖励';

					//招商奖励大于0
					if($type==1 && $data['recommend_reward']>0) {
						//tp的BUG 第二次开始要手工指定主键ID
						isset($orderReward->id) && $data['id']=$orderReward->id+1;
						$orderReward->isUpdate(false)->save($data);
					}

					$type==2 && $finalResult[]=$data;
				}
			}
		}
	}

	if($type==2) {
		return $finalResult;
	}
}

//获取代理的间接销售奖励
/*
* orderAmount 产品订单金额
* $ratioDiff       角色
* hierarchy  层级
* 获取一个产品中的，特定角色的，层级的，奖励金额
* $type 1是不加库存  2是加库存
*/
function get_agent_agency_reward($orderAmount,$ratioDiff,$stock_money,$type=1)
{
	$res=0;

	!empty($ratioDiff) && $res=round($orderAmount*$ratioDiff/100,2);
	if($type==2 && !empty($ratioDiff)) {
		$res=round($orderAmount*$ratioDiff/100,2)+$stock_money;
	}

	return $res;

}

/*
 * productId 产品ID
 * orderAmount 订单金额
 * role       角色
 * hierarchy  层级
 * 获取一个产品中的，特定角色的，层级的，奖励金额
 */
function get_agent_recommend_reward($productId,$orderAmount,$role,$hierarchy)
{
	$res=0;

	//系统招商奖励表模型
	$reward=model('admin/Agentrewardrecommend');

	//产品招商奖励表模型
	$productReward=model('admin/Productrecommendreward');

	//根据用户角色查找对应的产品表中存的系数
	$productRewardInfo=$productReward->where(array('product_id'=>$productId,'role'=>$role,'hierarchy'=>$hierarchy))->find();

	if(!empty($productRewardInfo)) {
		$res=round($orderAmount*$productRewardInfo['value']/100,2);
	} else {
		//产品表的没有，那么获取系统表的系数，然后相乘
		$rewardInfo=$reward->where(array('role'=>$role,'hierarchy'=>$hierarchy))->find();
		!empty($rewardInfo['value']) && $res=round($orderAmount*$rewardInfo['value']/100,2);
	}


	return $res;
}


//根据地址ID获取地址名称
function get_address_name_by_id($id)
{
	$res=model('BasicDataAddress')->field('name')->find($id);

	return $res['name'];
}


function get_product_name_byproduct_id($productId)
{
    $category = model('Productcategory');
    $res=$category->find($productId);
    return $res['category_name'];
}

/**
 * 获取指定数组的维数
 *
 * @param $arr array 指定的数组
 * @return Int
 */
function count_array_dimension($arr)
{
    $al = array(0);
    aL($arr,$al);
    return max($al);// 返回最大值
}

/**
 * 获取维数配合函数
 *
 * @param $arr array 数组
 * @param $al array 沿途统计的维数:array(0,1,2,...,n)
 * @param $level int 本维的维数:1,2,...,n
 */
function aL($arr,&$al,$level=0)
{
    if(is_array($arr))
    {
        $level++;// 维数增加
        $al[] = $level;
        foreach($arr as $v)
        {
            aL($v,$al,$level);// 递归查询
        }
    }
}


//获取类目名称串
function get_category_name_str($pid,$type=1)
{
	$res=array();
	$result='';//最终输出的结果

	$category=model('Productcategory');

	//获取当前类目信息
	$categoryInfo=$category->find($pid);
	$res[0]=$categoryInfo['category_name'];

	//寻找上级
	if($categoryInfo['parent_id'])
	{
		$oneLevelInfo=$category->find($categoryInfo['parent_id']);
		$res[1]=$oneLevelInfo['category_name'];

		//寻找第三级
		if($oneLevelInfo['parent_id'])
		{
			$towLevelInfo=$category->find($oneLevelInfo['parent_id']);
			$res[2]=$towLevelInfo['category_name'];

			//寻找第四级
			if($towLevelInfo['parent_id'])
			{
				$threeLevelInfo=$category->find($towLevelInfo['parent_id']);
				$res[3]=$threeLevelInfo['category_name'];
			}
		}
	}

	//数组反转
	$res=array_reverse($res);

	foreach($res as $k=>$v)
	{
	    if($k==3){
            $result.=$v;
        } else {
            $result.=$v.'/';
        }
	}

	return $result;
}
/*
               * 适用于 PHP 5.4 更更早版本的 array_column() 函数
               * @param array $input 原始数组
               * @param string|integer|null $column_key 键名
               * @param string|integer $index_key 原始数组中作为结果数组键名的键名
               * @return null|array|false
 *
              */
function array_column_s($input,$column_key,$index_key=''){
    if(!is_array($input)) return;
    $results=array();
    if($column_key===null){
        if(!is_string($index_key)&&!is_int($index_key)) return false;
        foreach($input as $_v){
            if(array_key_exists($index_key,$_v)){
                $results[$_v[$index_key]]=$_v;
            }
        }
        if(empty($results)) $results=$input;
    }else if(!is_string($column_key)&&!is_int($column_key)){
        return false;
    }else{
        if(!is_string($index_key)&&!is_int($index_key)) return false;
        if($index_key===''){
            foreach($input as $_v){
                if(is_array($_v)&&array_key_exists($column_key,$_v)){
                    $results[]=$_v[$column_key];
                }
            }
        }else{
            foreach($input as $_v){
                if(is_array($_v)&&array_key_exists($column_key,$_v)&&array_key_exists($index_key,$_v)){
                    $results[$_v[$index_key]]=$_v[$column_key];
                }
            }
        }

    }
    return $results;
}

/**
 * 获取指定长度的随机字符串
 *
 * @param $len int 指定生成长度
 * @param $type string 生成类型:d全数字,非d数字字母组合
 * @return string
 */
function get_mt_rand($len = 6,$type = 'd')
{
	$str = '';
	$arr = [0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
	if($type == 'd')
	{
		$max = 9;
	}else{
		$max = 61;
	}
	for ($i=0; $i < $len; $i++)
	{
		$str .= $arr[mt_rand(0,$max)];
	}
	return $str;
}

/**
 * 发送短信验证码
 *
 * @param $content array 必须参数
 * @return array
 */
function send_sms($content)
{
	if(empty($content['phone']) || empty($content['code']))
	{
		return false;
	}
	vendor('SignatureHelper.SignatureHelper');
    $sms = new \Aliyun\DySDKLite\SignatureHelper();
    $accessKeyId     = config('sms.accessKeyId');
    $accessKeySecret = config('sms.accessKeySecret');
    $params = [];
    $params["PhoneNumbers"]  = $content['phone'];// 目标手机号
    $params['SignName']      = '森林舞会';// 签名名称
    $params["TemplateCode"]  = $content['template'];// 模板CODE
    $params['TemplateParam'] = [
        "code" => $content['code']
    ];
    if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
        $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
    }
    $return = $sms->request(
        $accessKeyId,
        $accessKeySecret,
        "dysmsapi.aliyuncs.com",
        array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
        ))
    );
    return $return ? get_object_vars($return) : false;
}

//获取奖励的等级名称
function get_reward_levelname($id)
{
	$dict=model('admin/Basicnamedictionary');
	$result=$dict->where("value='outsider'")->value('name');

	$res=model('Agentlevel')->field('name')->find($id);
	!empty($res['name']) && $result=$res['name'];

	return $result;
}

//获取营销活动中的大礼包的奖励,type=1是写入数据库 2是返回结果的数组
function promotion_gift_reward($order_number,$type=1)
{
	//思路整理
	//根据订单号，查找订单中的大礼包ID，根据大礼包id查找对应的奖励方式，根据奖励方式计算收益，写到数据库表中
	$giftorder=model('Promotiongiftorder');
	$orderInfo=$giftorder->where("order_number='".$order_number."'")->find();


	$agent=model('Agents');
	$userInfo=$agent->find($orderInfo['agent_id']);


	//获取所有上级
	$parentIdStr=$userInfo['family'];
	if(!empty($parentIdStr))
	{
		//提取前6个到数组
		$parentIdAry=explode(',',$parentIdStr);
		$realAry=array_reverse(array_slice($parentIdAry,0,5));//数组提取，反转

		$orderReward=model('Promotiongiftorderreward');

		//循环查询礼包推广奖励收益，然后存到礼包奖励表
		$data=array();
		foreach($realAry as $kk=>$vv)
		{
			$tmpUserInfo=$agent->find($vv);
			$data[$kk]['recommend_reward']=get_promotion_gift_reward($orderInfo['order_price'],$orderInfo['gift_id'],$tmpUserInfo['role'],$kk+1);
			if($data[$kk]['recommend_reward']==0){
				unset($data[$kk]);
				break;
			}
			$data[$kk]['recommend_hierarchy']=$kk+1;

			$data[$kk]['create_time']=time();
			$data[$kk]['gift_id']=$orderInfo['gift_id'];
			$data[$kk]['reward_type']=1;
			$data[$kk]['agent_id']=$tmpUserInfo['agent_id'];
			$data[$kk]['order_number']=$order_number;
		}

		$type==1 && $orderReward->saveAll($data);//添加到数据
		//2返回结果数组
		if($type==2) {
			return $data;
	}
	}


}

//获取每个层级获取的礼包奖励
function get_promotion_gift_reward($order_amount,$gift_id,$role,$hierarchy)
{
	$res=0;

	//礼包推荐奖励表模型
	$orderReward=model('Promotiongiftreward');

	//根据用户角色查找对应的大礼包表中存的系数
	$rewardInfo=$orderReward->where(array('gift_id'=>$gift_id,'role'=>$role,'hierarchy'=>$hierarchy))->find();

	if(!empty($rewardInfo['value'])) {
		$res=round($order_amount*$rewardInfo['value']/100,2);
	}

	return $res;
}

//获取直属下级代理
function get_direct_lower_agent($aid,$role,$resultID)
{
    //操作代理商表
    $Agents=model('Agents');

    $userInfo=$Agents->find($aid);
    //直属代理商 的数据获取逻辑方法。
    $agentLevel = model('Agentlevel');

    //通过登录用户的role循环查询比他等级低的全部身份
    $roleList=$agentLevel->order('id','desc')->select();
    $levelAry=array();
    foreach($roleList as $k=>$v) {
        $role>$v['id'] && $levelAry[]=$v['id'];
    }
    //通过上面获取的值最后组装数据
    $result=array();
    foreach($levelAry as $k=>$v){
        $result[$k]['role']= $v;
        $result[$k]['count']=$Agents->where(['agent_id'=>['in',$resultID],'role'=>$v])->count();
    }
    return $result;
}

//获取直属特定等级的下级代理
function get_direct_role_agent($userId,$role)
{
    //操作代理商表
    $Agents=model('Agents');
    $userInfo=$Agents->find($userId);
    $resultID = model('Agents')->getSons($userId,$userInfo['role'],4);//获取直属代理的信息
    $userList = $Agents->where(['agent_id'=>['in',$resultID],'role'=>$role])->select();
    $result=array();
    foreach($userList as $k=>$v){
        $result[$k]['agent_id']=$v['agent_id'];
        $result[$k]['head_img']=$v['head_img'];
        $result[$k]['generation']=$v['generation'];
        $result[$k]['nickname']=$v['nickname'];
        $result[$k]['directly_count']=count(model('Agents')->getSons($v['agent_id'],$v['role'],4));
        $result[$k]['recommend_count']= $Agents->where(['inviter'=>$v['agent_id']])->where('is_del','EQ',0)->count();
    }

    return $result;

}
//推荐代理身份和数量的获取
function get_recommend_agent($userId)
{
    //操作代理商表
    $Agents=model('Agents');
    $recommend = $Agents->where('inviter',$userId)->where('role','NEQ',0)->where('is_del','eq',0)->select();
 //    $sql="SELECT * FROM agents WHERE FIND_IN_SET('$userId', inviter)";
    $uidLowAry=array();
//    $result2 = $Agents->query($sql);
     foreach ($recommend as $k=>$value){
        $uidLowAry[]= $value['role'];
    }

    $result = array_count_values($uidLowAry);

    $default = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0);//默认身份数量

//    $return = array_merge((array)$default,(array)$result);
    $return = $result + $default;
    ksort($return);
     return $return;

  }
  //根据二级用户ID获取推荐代理的信息
function get_recommend_list_agent($userId,$rank)
{
    //操作代理商表
    $Agents=model('Agents');
    $data=array();
    $data['inviter']=$userId;
    $data['role']=$rank;
    $data['is_del']=0;
    $userList = $Agents->where($data)->select();
    foreach ($userList as $k=>&$v){
        $v['directly_count']=count(model('Agents')->getSons($v['agent_id'],$v['role'],4));
        $v['recommend_count']= $Agents->where(['inviter'=>$v['agent_id']])->where('is_del','EQ',0)->count();
    }

    return $userList;
}


//根据产品ID获取产品信息
function get_product_info_by_product_id($productId,$field='*')
{
	$res='';
	$info=model('Productmanagement')->field($field)->find($productId);

	$res=$info;
	if($field!='*') {
		$res=$info[$field];
	}
	return $res;

}

/**
 * 二维数组根据字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 */
function array_sequence($array, $field, $sort = 'SORT_DESC')
{
	$arrSort = array();
	foreach ($array as $uniqid => $row) {
		foreach ($row as $key => $value) {
			$arrSort[$key][$uniqid] = $value;
		}
	}
	array_multisort($arrSort[$field], constant($sort), $array);
	return $array;
}

//根基id获取代理商微信昵称
function get_agent_name_by_id($id)
{
    $res=model('Agents')->field('nickname')->find($id);

    return $res['nickname'];
}


//根据订单获取可以获得间接销售收益的代理的用户信息
function get_agent_agency_reward_user($orderNumber,$product_id)
{
	$finalUserAry=array();

	//根据订单号 获取订单信息
	$orders=model('admin/Agentorders');
	$orderInfo=$orders->where("order_number='".$orderNumber."' and pid=".$product_id)->find();

	//根据订单的用户ID 获取用户的信息
	$agent=model('Agents');
	$userInfo=$agent->find($orderInfo['agent_id']);

	//等级校验
	if(!empty($userInfo['inviter'])) {
		//如果上级比下级大，那么用上级的等级，如果不是那么用下级的
		$inviterInfo=$agent->find($userInfo['inviter']);
		if($inviterInfo['role']>=$userInfo['role']) {
			$userInfo=$agent->find($userInfo['inviter']);
		}
	}
 
	//根据用户ID角色获取代理商收入设置信息
	$agency=model('admin/Agentrewardagency');


	//间接收入奖励是否启用
	$config=model('admin/Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();

	$productReward=model('admin/Productagentreward');

	$orderReward=model('admin/Agentorderreward');

	//根据用户信息 获取用户的所有上级
	$parentIdStr=$userInfo['family'];
	if(!empty($parentIdStr) && $configInfo['valid_agency_reward']==1)
	{
		$middelUserAry=array();//中间用户数组
		$parentIdAry=explode(',',$parentIdStr);
		$parentIdAry=array_reverse($parentIdAry);

		//循环自己的上级，把比自己等级高的放到一个数组
		$i=0;
		foreach($parentIdAry as $k=>$v)
		{
			$tmpUserInfo=$agent->find($v);//获取用户信息
			$tmpLevelInfo=$agency->getLevelInfo($tmpUserInfo['role']);//获取角色对应的奖励系数

			if($tmpUserInfo['role']>$userInfo['role']) {
				$middelUserAry[$i]['agent_id']=$v;
				$middelUserAry[$i]['role']=$tmpUserInfo['role'];

				$productRatio=$productReward->where('product_id='.$product_id.' and role='.$tmpUserInfo['role'])->value('agent_reward');
				if($productRatio) {
					$middelUserAry[$i]['ratio']=$productRatio;
				} else {
					$middelUserAry[$i]['ratio']=$tmpLevelInfo['ratio'];
				}
				//校验下产品表是否有数据，如果有那么优先获取
				$middelUserAry[$i]['lowest_limit']=$tmpLevelInfo['lowest_limit'];
				$middelUserAry[$i]['stock_money']=$tmpUserInfo['stock_money'];
			}
			$i++;
		}
		
		//重新组织数组-避免平级的获取不到
		$middelUserAry=array_slice($middelUserAry,0);
 
		if(!empty($middelUserAry))
		{
			//把比自己等级高的数组开始循环
			foreach($middelUserAry as $k=>$v)
			{
				//循环判断 获取比例的差级
				$finalUserAry[$k]=$v;
				if($k==0) {

					$realRole=$userInfo['role'];//用于计算比例差的角色等级

					//获取比例，优先冲产品里面获取
					$productRatio=$productReward->where('product_id='.$product_id.' and role='.$realRole)->value('agent_reward');

					$userLevelInfo=$agency->getLevelInfo($realRole);
					$ratio=$userLevelInfo['ratio'];

					$productRatio && $ratio=$productRatio;//获取比例差

					$tmp=$v['ratio']-$ratio;//比例差
					$tmp>0 && $finalUserAry[$k]['ratioDiff']=$tmp;
				} else {
					$tmp=$v['ratio']-$middelUserAry[$k-1]['ratio'];//比例差
					$tmp>0 && $finalUserAry[$k]['ratioDiff']=$tmp;

				}

				$finalUserAry[$k]['minusStock']=round($orderInfo['ptotal_price_pay']*(1-($v['ratio']/100)),2);//减的库存

			}
		}
	}

	return $finalUserAry;
}


//发放代理一个月的代理业绩奖励
function get_agent_performance($cyear,$cmonth,$agentId)
{
	/*本月绩效奖励基数*绩效增长系数*代理商级别系数
	绩效奖励基数=本人当月所获代理收益+本人当月所获推荐提成收益+当月大礼包所获推荐提成收益
	绩效增长系数=本月绩效奖励基数/上月绩效奖励基数
 	*/
	$res=0;

	$reward=model('Agentorderreward');
	$gift=model('Promotiongiftorderreward');
	$agent=model('Agents');
	$performance=model('Agentperformancereward');

	//先来计算绩效奖励基数
	$performanceBase=0;

	//1本人当月所获代理收益（直接销售和间接销售） 20180716 此改完间接收益 由于有历史数据  所以代码不变
	$agentRewardDirect=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>2,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('directsale_reward');

	$agentRewardInDirect=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>3,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('wholesale_reward');
	//2本人当月所获招商提成收益
	$agentRewardRecommend=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>1,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('recommend_reward');

	//下级升级库存奖励5
	$chargeReward=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>5,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('charge_reward');
	//库存充值产生的记录 8
	$chargeReward_charge=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>8,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('charge_reward');

	//3当月其他奖励  20180821 礼包奖励 改为其他奖励  内容是礼包和库存充值奖励之和
	$promotionReward=$gift->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>1,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('recommend_reward');
	$otherReward=round($promotionReward,2);

	//4 获取直接奖励收益 20180716 增加和第一个有所区别
	$agentRewardInDirectPlus=$reward->where(array('agent_id'=>$agentId,'status'=>2,'reward_type'=>4,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%m')"=>$cmonth,"DATE_FORMAT(FROM_UNIXTIME(update_time),'%Y')"=>$cyear))->sum('selfsale_reward');

	//获取绩效奖励基数
	$performanceBase=$agentRewardDirect+$agentRewardInDirect+$agentRewardRecommend+$otherReward+$agentRewardInDirectPlus+$chargeReward+$chargeReward_charge;

	//然后计算绩效增长系数

	//根据用户的注册时间，来获取注册时间和代理商级别
	$agentInfo=$agent->find($agentId);

	//获取注册的时间，年和月都要对比
	$register_year=date('Y',strtotime($agentInfo['create_ctime']));
	$register_month=date('m',strtotime($agentInfo['create_ctime']));


	//获取上月绩效增长系数，注册当月为1
	$lastPerformanceBase=$performanceIncrease=0;

	$lastPerformanceBase=1;//获取上月绩效增长系数，默认也为1
	if($register_year==$cyear && $register_year==$cmonth) {
		$lastPerformanceBase=1;
	} else {
		//读取绩效奖励表中的上个月的本月奖励系数，如果没有，也设置为0
		$performanceInfo=$performance->where(array('agent_id'=>$agentId,'year'=>$cyear,'month'=>($cmonth-1)))->find();
		if($performanceInfo['current_performance_base']>0) {
			$lastPerformanceBase=$performanceInfo['current_performance_base'];
		}

	}
	//获取绩效增长系数
	$lastPerformanceBase>0 && $performanceIncrease=$performanceBase/$lastPerformanceBase;
	$performanceIncrease>200 && $performanceIncrease=200;//最多200


	//根据用户信息,获取当前等级的奖励系数比例
	$levelInfo=model('Agentrewardperformance')->where(array('role'=>$agentInfo['role']))->find();

	//计算绩效奖励
	$res=round($performanceBase*$performanceIncrease/100*$levelInfo['ratio']/100,2);

	//插入到数据表
	if($res>0) {
		//校验下是否已插入
		$count=$performance->where(array('year'=>$cyear,'month'=>$cmonth,'agent_id'=>$agentId))->count();

		if($count==0) {
			$data=array();
			$data['agent_id']=$agentId;
			$data['create_time']=time();
			$data['month']=$cmonth;
			$data['year']=$cyear;
			$data['performance_profit']=$res;
			$data['increate_ratio']=$performanceIncrease;
			$data['last_performance_base']=$lastPerformanceBase;
			$data['current_performance_base']=$performanceBase;
			$data['current_agent_profit']=round($agentRewardDirect+$agentRewardInDirect+$agentRewardInDirectPlus+$chargeReward_charge+$chargeReward,2);
			$data['current_recommend_profit']=round($agentRewardRecommend,2);
			$data['current_promotion_gift_profit']=round($otherReward,2);
			$data['status']=1;

			//tp的BUG 第二次开始要手工指定主键ID
			isset($performance->id) && $data['id']=$performance->id+1;
			/*同一个实例多次添加，需要设置isupdate*/
			$res=$performance->isUpdate(false)->save($data);

		}
	}
}


//发放订单的奖励
function provide_order_reward($orderNumber)
{
	//加载订单奖励模型
	$reward=model('Agentorderreward');

	//加载代理收益表模型
	$profit=model('Agentprofit');

	//代理商
	$agent=model('Agents');

	//获取订单表中的奖励数据
	$rewardList=$reward->where("order_number='".$orderNumber."' and status=1")->select();

	if(!empty($rewardList)) {

		//事务开始
		Db::startTrans();//开启事务
		try{

			//更新订单奖励表的状态 设置已结算
			$data=array();
			$data['status']=2;
			$data['update_time']=time();
			$reward->save($data,array('order_number'=>$orderNumber,'status'=>1));

			foreach($rewardList as $k=>$v)
			{

				//添加到代理收益记录表
				$rdata=array();
				$rdata['agent_id']=$v['agent_id'];
				$rdata['create_time']=time();
				$rdata['order_number']=$v['order_number'];
				$rdata['product_id']=$v['product_id'];
				if($v['reward_type']==1) {
					$rdata['profit']=$v['recommend_reward'];
					$rdata['type']=3;
				} else if($v['reward_type']==2) {
					$rdata['profit']=$v['directsale_reward'];
					$rdata['sales_amount']=$v['sales_amount'];
					$rdata['type']=1;
				} else if($v['reward_type']==3) {
					$rdata['profit']=$v['wholesale_reward'];
					$rdata['sales_amount']=$v['sales_amount'];
					$rdata['type']=2;
				}else if($v['reward_type']==4) {
					$rdata['profit']=$v['selfsale_reward'];
					$rdata['type']=6;
					$rdata['sales_amount']=$v['sales_amount'];
				}

				//校验下是否已添加
				$count=$profit->where("agent_id=".$v['agent_id']." and order_number='".$v['order_number']."' and type=".$rdata['type'].' and product_id='.$v['product_id'])->count();

				if($count==0) {
					//tp的BUG 第二次开始要手工指定主键ID
					isset($profit->id) && $rdata['id']=$profit->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$res=$profit->isUpdate(false)->save($rdata);
					
					//奖励发放 
					$result=$agent->where('agent_id',$v['agent_id'])->setInc('profit',$rdata['profit']);
				}

				//通知发放
				$agentInfo=model('index/Agents')->field('phone')->find($v['agent_id']);
				$rewardType=model('admin/Agentorderreward')->rewardType;

				$mdata=array();
				$mdata['first']='您的收益已经发放！';
				$mdata['account']=$agentInfo['phone'];
				$mdata['time']=date('Y-m-d H:i:s');
				$mdata['type']=$rewardType[$v['reward_type']];
				$mdata['remark']='关联订单号'.$rdata['order_number'].',收益金额'.$rdata['profit'].',请注意查收';

				//给下单人发送发货通知
				message_notification(3,$v['agent_id'],$mdata);

			}

			// 提交事务
			Db::commit();

		}catch (\Exception $e) {
    		// 回滚事务
    		Db::rollback();
		}

	}


	return 1;
}


//发放单一代理商的绩效的奖励
function provide_performance_profit($agentId,$month,$year)
{
	//加载代理收益表模型
	$profit=model('Agentprofit');

	//加载绩效奖励表的内容
	$performance=model('Agentperformancereward');

	//代理商
	$agent=model('Agents');

	//获取订单表中的奖励数据
	$performanceInfo=$performance->where(array('month'=>$month,'year'=>$year,'agent_id'=>$agentId,'status'=>1))->find();

	if(!empty($performanceInfo['id'])) {

		//事务开始
		Db::startTrans();//开启事务
		try{

			//更新绩效奖励表的状态 设置已结算
			$data=array();
			$data['status']=2;
			$data['update_time']=time();
			$performance->update($data,array('id'=>$performanceInfo['id']));


			//校验下是否已添加
			$count=$profit->where(array('agent_id'=>$agentId,'year'=>$year,'month'=>$month,'type'=>4))->count();
			if($count==0) {

				//添加到代理收益记录表
				$rdata=array();
				$rdata['agent_id']=$agentId;
				$rdata['create_time']=time();
				$rdata['profit']=$performanceInfo['performance_profit'];
				$rdata['year']=$year;
				$rdata['type']=4;//绩效奖励
				$rdata['month']=$month;

				//tp的BUG 第二次开始要手工指定主键ID
				isset($profit->id) && $rdata['id']=$profit->id+1;
				/*同一个实例多次添加，需要设置isupdate*/
				$res=$profit->isUpdate(false)->save($rdata);

				//代理账户进行增加
				$result=$agent->where('agent_id',$agentId)->setInc('profit',$rdata['profit']);
			}


			// 提交事务
			Db::commit();

		}catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
		}

	}


}


//礼包订单奖励发放到收益表
function provide_promotion_gift_order_reward_profit($orderNumber)
{
	//加载代理收益表模型
	$profit=model('Agentprofit');

	//加载大礼包奖励表的内容
	$giftreward=model('Promotiongiftorderreward');

	//代理商
	$agent=model('Agents');

	//获取订单表中的奖励数据
	$rewardList=$giftreward->where(array('order_number'=>$orderNumber))->select();


	foreach($rewardList as $k=>$v)
	{
		//事务开始
		Db::startTrans();//开启事务
		try{

			//更新绩效奖励表的状态 设置已结算
			$data=array();
			$data['status']=2;
			$data['update_time']=time();
			$giftreward->update($data,array('id'=>$v['id']));

			//校验下是否已添加
			$count=$profit->where("agent_id=".$v['agent_id']." and order_number='".$v['order_number']."' and type=5")->count();
			if($count==0) {

				//添加到代理收益记录表
				$rdata=array();
				$rdata['agent_id']=$v['agent_id'];
				$rdata['create_time']=time();
				$rdata['profit']=$v['recommend_reward'];
				$rdata['type']=5;//绩效奖励
				$rdata['order_number']=$v['order_number'];

				//tp的BUG 第二次开始要手工指定主键ID
				isset($profit->id) && $rdata['id']=$profit->id+1;
				/*同一个实例多次添加，需要设置isupdate*/
				$res=$profit->isUpdate(false)->save($rdata);

				//代理账户进行增加
				$result=$agent->where('agent_id',$v['agent_id'])->setInc('profit',$rdata['profit']);
			}


			// 提交事务
			Db::commit();

		}catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
		}

	}

}

/**
 * 核算单个订单成本价
 *
 * @param $price float 单价
 * @param $num int 数量
 * @param $percent float 比例(折扣)
 * @param $other float 其他成本
 */
function sum_order_cost($price,$num,$percent,$other=0)
{
	return round(($price * $num * $percent) + $other,2);
}


//消息通知
/**
 *
 * @param int $type
 * @param int $agent_id
 * @param array $info
 */
function message_notification($type,$agent_id,$info)
{
	//先校验下 是否打开，然后是否有openid
	$weixin=model('Weixinusers');
	$weixinInfo=$weixin->where('agent_id='.$agent_id)->find();

	//加载模型 消息发送记录
	$log=model('admin/Weixinmessagelog');

	$oauth = controller('index/Oauth', 'controller');

	$dataInfo=message_setting($type,$info);//加载消息发送设置

	if($weixinInfo['openid'] && config('web.weixin_message') && !empty($dataInfo))
	{
		$oauth->sendMessage($weixinInfo['openid'],$dataInfo);

		//存到消息记录表
		$data=array();
		$data['agent_id']=$agent_id;
		$data['content']=$info['remark'];
		$data['openid']=$weixinInfo['openid'];
		$data['type']=$type;
		$data['create_time']=time();
		isset($log->id) && $data['id']=$log->id+1;
		$log->isUpdate(false)->save($data);
	}

}

//消息格式设置
function message_setting($type,$info=array())
{
	$res='';
	$data=array();


	//购买成功通知
	$type==1 &&$data[1]=array(
		'template_id'=>config('weixin_template_id.1'),
		'data'=>array(
			'name'  =>array('value'=>$info['name']),
			'remark'=>array('value'=>$info['remark'])
		)
	);

	//订单标记发货通知
	$type==2 && $data[2]=array(
		'template_id'=>config('weixin_template_id.2'),
		'url'=>$info['url'],
		'data'=>array(
			'first'=>array('value'=>$info['first']),
			'orderProductPrice'=>array('value'=>$info['orderProductPrice']),
			'orderProductName'=>array('value'=>$info['orderProductName']),
			'orderName'=>array('value'=>$info['orderName']),
			'remark'=>array('value'=>$info['remark']),
		),
	);


	//账户变更通知
	$type==3 && $data[3]=array(
		'template_id'=>config('weixin_template_id.3'),
		'data'=>array(
			'first'=>array('value'=>$info['first']),
			'account'=>array('value'=>$info['account']),
			'time'=>array('value'=>$info['time']),
			'type'=>array('value'=>$info['type']),
			'remark'=>array('value'=>$info['remark']),
		),
	);

	return $data[$type];

}


//校验是否是移动端设备
function isMobile()
{
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
		return true;
	}
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset($_SERVER['HTTP_VIA'])) {
		// 找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger');
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	// 协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
	return false;
}

//根据不同的方法名称，返回不同的URL
function get_returnurl_by_actionname($actionname)
{
	$returnUrl='index/index/index';

	switch($actionname) {

		case 'personalinfolist':$returnUrl='index/person/index';break;
		case 'agencyteam':$returnUrl='index/person/index';break;
		case 'agencylevel':$returnUrl='index/personteam/agencyteam';break;
		case 'myprofit':$returnUrl='index/person/myassets';break;
		case 'mystock':$returnUrl='index/person/myassets';break;
		case 'myincome':$returnUrl='index/person/myassets';break;
		case 'invitereward':$returnUrl='index/person/myincome';break;
		case 'agentincome':$returnUrl='index/person/myincome';break;
		case 'promotionreward':$returnUrl='index/person/myincome';break;
		case 'achievementreward':$returnUrl='index/person/myincome';break;
		case 'privilegeteamperformance':$returnUrl='index/index/teamperformance';break;


		default:$returnUrl='index/index/index/';break;
	}

	return url($returnUrl);
}

//消息发送-库存不足通知
function message_send_stock_not_enough($agent_id)
{
	$agents=model('admin/Agents');
	$info=$agents->field('agent_id,phone')->find($agent_id);

	$mdata=array();
	$mdata['first']='您的库存不足！';
	$mdata['account']=$info['phone'];
	$mdata['time']=date('Y-m-d H:i:s');
	$mdata['type']='库存不足';
	$mdata['remark']='因您的库存不足，无法享受代理收益，请及时充值库存';

	//发送通知
	message_notification(3,$info['agent_id'],$mdata);
}

/**
 * 计算团队业绩 2018-04-19
 * @param  [type] $arr      代理商ID[n1,n2,...,n]
 * @param  array  $arr_time 时间戳类型时间段:x小于等于y[x,y]
 * @return float           总金额
 */
function count_team_orders_sales_total($arr,$arr_time=[])
{
	$count = $total = 0;
	$arr   = array_values($arr);
	$count = count($arr);
	$m_index_agents = model('index/Agents');
	$m_index_order_reward = model('index/Agentorderreward');

	for ($i=0; $i < $count; $i++)
	{
		$arr_role = $m_index_agents->getAgentInfoByAid($arr[$i],'role');
		$total += $m_index_order_reward->getSaleTotalByAgentId($arr[$i],$arr_time) + $m_index_order_reward->getOnlyNoRewardOrderSale($arr[$i],$arr_role['role'],$arr_time);
	}
	return $total;
}

/**
 * 统计所有代理商的排行数据并插入到排行榜表
 */
function count_all_agent_data_for_rank()
{
	$all_agents = model('index/Agents')->where(['is_del'=>0])->count();// 有效代理商总数
	/*if($all_agents < 1000)
	{
		$total = ceil($all_agents/1000);
		for($i = 0; $i < $total; $i++)
		{
			$add_data = [];
			$list = model('index/Agents')->where(['is_del'=>0])->limit($i*1000,1000)->select();
			foreach($list as $val)
			{
				$data_cache = [];
				$data_cache['a_id']          = $val['agent_id'];
				$data_cache['sales_money']   = count_team_orders_sales_total([$val['agent_id']]);// 销售总额
				$data_cache['rewards_money'] = model('index/Agentprofit')->getRewardByDefined(['agent_id'=>$val['agent_id']]);// 奖励总额
				$data_cache['recommend_num'] = model('index/Agents')->where(['inviter'=>$val['agent_id'],'is_del'=>0])->count();// 推荐人数
				$data_cache['team_num']      = count(model('index/Agents')->getSons($val['agent_id'],$val['role'],4));// 代理团队
				$add_data[] = $data_cache;
			}
			model('admin/Agentrank')->saveAll($add_data);// 保存|更新
		}
	}*/
	$list = model('index/Agents')->where(['is_del'=>0])->select();
	foreach ($list as $key => $val)
	{
		$data_cache = [];
		$data_cache['a_id']          = $val['agent_id'];
		$data_cache['sales_money']   = count_team_orders_sales_total([$val['agent_id']]);// 销售总额
		$data_cache['rewards_money'] = model('index/Agentprofit')->getRewardByDefined(['agent_id'=>$val['agent_id']]);// 奖励总额
		$data_cache['recommend_num'] = model('index/Agents')->where(['inviter'=>$val['agent_id'],'is_del'=>0])->count();// 推荐人数
		$data_cache['team_num']      = count(model('index/Agents')->getSons($val['agent_id'],$val['role'],4));// 代理团队
		$isset = model('admin/Agentrank')->where(['a_id'=>$data_cache['a_id']])->count();
		if($isset)
		{
			model('admin/Agentrank')->update($data_cache);// 保存|更新
		}else{
			model('admin/Agentrank')->insert($data_cache);// 保存|更新
		}
	}
	// 获取已刪除的代理商
	$del_list = model('index/Agents')->where(['is_del'=>1])->column('agent_id');
	if($del_list)
	{
		model('admin/Agentrank')->where(['a_id'=>['in',implode(',', $del_list)]])->update(['is_del'=>1]);
	}
	// 更新排名
	model('admin/Agentrank')->query('UPDATE agents_data_count_for_rank AS t1 JOIN ( SELECT a_id,(@rowno :=@rowno + 1) AS rank FROM agents_data_count_for_rank, (SELECT (@rowno := 0)) b WHERE is_del=0 ORDER BY team_num DESC,a_id ASC ) AS t2 SET t1.team_rank = t2.rank WHERE t1.a_id = t2.a_id');// 团队
	model('admin/Agentrank')->query('UPDATE agents_data_count_for_rank AS t1 JOIN ( SELECT a_id,(@rowno :=@rowno + 1) AS rank FROM agents_data_count_for_rank, (SELECT (@rowno := 0)) b WHERE is_del=0 ORDER BY recommend_num DESC,a_id ASC ) AS t2 SET t1.recommend_rank = t2.rank WHERE t1.a_id = t2.a_id');// 推荐
	model('admin/Agentrank')->query('UPDATE agents_data_count_for_rank AS t1 JOIN ( SELECT a_id,(@rowno :=@rowno + 1) AS rank FROM agents_data_count_for_rank, (SELECT (@rowno := 0)) b WHERE is_del=0 ORDER BY rewards_money DESC,a_id ASC ) AS t2 SET t1.reward_rank = t2.rank WHERE t1.a_id = t2.a_id');// 奖励
	model('admin/Agentrank')->query('UPDATE agents_data_count_for_rank AS t1 JOIN ( SELECT a_id,(@rowno :=@rowno + 1) AS rank FROM agents_data_count_for_rank, (SELECT (@rowno := 0)) b WHERE is_del=0 ORDER BY sales_money DESC,a_id ASC ) AS t2 SET t1.sale_rank = t2.rank WHERE t1.a_id = t2.a_id');// 销量
}



//代理商，注册申请身份变更后自动下单
function agent_auto_order($agent_id)
{
	$result=0;

	//先校验下，身份是从申请这种流程变更的，然后没有下单
	$apply=model('admin/AgentApplications');
	$order=model('admin/Agentorders');
	$product=model('admin/Productmanagement');
	$agent=model('admin/Agents');
	$reward=model('admin/Agentrewardagency');
	$orders = model('index/Orders');
	$address=model('index/AgentAddress');
	$stock=model('admin/Agentstockchange');

	//获取升级类型
	$applyType=$apply->getApplyType($agent_id);
	//获取订单数量
	$orderNum=$order->getAgentsOrderNum($agent_id);
	//获取首单的产品ID
	$productId=$product->getFirstOrderProductId();


	//申请升级的 并且没有下过单的，系统自动下首单
	if($applyType==1 && $orderNum==0  && isset($productId))
	{

		//思路整理，找到用户的等级，找到这个等级的最低库存比例，然后找到商品的价格
		//根据库存比例和商品的价格  取整  然后生成一个订单
		$userInfo=$agent->find($agent_id);
		$agencyInfo=$reward->getLevelInfo($userInfo['role']);

		//获取首单的金额
		$firstOrderAmount=$agencyInfo['first_goods_money'];

		//获取商品单价
		$productInfo=$product->find($productId);
		$productNum=floor($firstOrderAmount/$productInfo['sales_price']);

		//地址表获取最新的地址
		$addressInfo=$address->getDefault($agent_id);

		$res=0;
		//生成订单
		if($productNum>0 && $addressInfo['id'] && $userInfo['stock_money']>=$firstOrderAmount) {
			$addressInfo['id'] && $res=$orders->addOrder($agent_id,$addressInfo['id'],$productId,$productNum,1,3,'',$productInfo['exemption_from_postage']);
		}

		//如果订单成功，那么减掉库存
		if($res) {
			//根据订单号 获取订单金额
			$orderInfo=$orders->where("order_number='".$res."'")->find();
			if($orderInfo['order_amount_pay']) {

				//插入到库存变动表一条记录
				//写库存变动记录
				$ndata=array();
				$ndata['agent_id']=$orderInfo['agent_id'];
				$ndata['create_time']=time();
				$ndata['change_before']=$userInfo['stock_money'];
				$ndata['money']=$orderInfo['order_amount_pay'];
				$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
				$ndata['change_type']=3;//减库存
				$ndata['account_type']=5;//其他

				$ndata['audit_time']=time();//审核时间
				$ndata['status']=2;//已审核
				$ndata['order_number']=$orderInfo['order_number'];
				$ndata['remark']='首单产品减库存';

				$resStock=$stock->save($ndata);


				//扣减库存
				$result=$agent->where('agent_id',$agent_id)->setDec('stock_money',$orderInfo['order_amount_pay']);

			}
		}

	}
	return $result;
}


//代理商订单库存支付 1是订单表，2是礼品订单表
function agent_order_stock_pay($order_number,$type=1)
{

	if(empty($order_number)) {
		return 0;
	}

	$result=0;
	//思路整理
	//使用事物机制

	$order=model('admin/Agentorders');
	$agent=model('admin/Agents');
	$stock=model('admin/Agentstockchange');
	$giftOrder=model('admin/Promotiongiftorder');

	//查询订单信息
	$orderInfo=array();
	$status=0;//订单状态

	$type==1 && $orderInfo=$order->where("order_number='".$order_number."'")->find();
	!empty($orderInfo) && $status=$orderInfo->getData('order_status');

	if($type==2){
		$orderInfo=$giftOrder->where("order_number='".$order_number."'")->find();
		$orderInfo['order_amount_pay']=$orderInfo['order_price'];
		$status=$orderInfo->getData('status');
	}


	//如果订单是库存支付
	if(!empty($orderInfo) && $orderInfo->getData('paystyle')==3 && $status==1) {

		//获取代理商信息
		$agentInfo=$agent->find($orderInfo['agent_id']);

		//校验库存是否充足
		if($agentInfo['stock_money']>=$orderInfo['order_amount_pay']) {

			//事务开始
			Db::startTrans();//开启事务
			try{

				//记录扣减库存日志
				//写库存变动记录
				$ndata=array();
				$ndata['agent_id']=$orderInfo['agent_id'];
				$ndata['create_time']=time();
				$ndata['change_before']=$agentInfo['stock_money'];
				$ndata['money']=$orderInfo['order_amount_pay'];
				$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
				$ndata['change_type']=3;//减库存
				$ndata['account_type']=5;//其他

				$ndata['audit_time']=time();//审核时间
				$ndata['status']=2;//已审核
				$ndata['order_number']=$orderInfo['order_number'];
				$type==1 && $ndata['remark']='商品订单库存支付减库存';
				$type==2 && $ndata['remark']='礼包订单库存支付减库存';

				$resStock=$stock->save($ndata);


				//扣减库存
				$agent->where('agent_id',$orderInfo['agent_id'])->setDec('stock_money',$orderInfo['order_amount_pay']);


				//变更支付状态
				$odata=array();
				$type==1 && $odata['order_status']=2;//支付成功
				$type==2 && $odata['status']=2;//支付成功
				$odata['pay_time']=time();

				$type==1 && $resPay=$order->save($odata,array('order_number'=>$order_number));
				$type==2 && $resPay=$giftOrder->save($odata,array('order_number'=>$order_number));

				//执行成功结果
				if($resPay && $resStock) {
					$result=1;
				}

				// 提交事务
				Db::commit();

			}catch (\Exception $e) {
				// 回滚事务
				Db::rollback();
			}

		}

	}

	return $result;

}

//代理商订单库存支付退款 type=1商品订单  2是礼包订单
function agent_order_stock_pay_refund($order_number,$type=1)
{
	if(empty($order_number)) {
		return 0;
	}

	$result=0;
	//思路整理
	//使用事物机制

	$order=model('admin/Agentorders');
	$agent=model('admin/Agents');
	$stock=model('admin/Agentstockchange');
	$giftOrder=model('admin/Promotiongiftorder');

	//查询订单信息
	$orderInfo=array();
	$status=0;//订单状态

	$type==1 && $orderInfo=$order->where("order_number='".$order_number."'")->find();
	!empty($orderInfo) && $status=$orderInfo->getData('order_status');

	if($type==2 ){
		$orderInfo=$giftOrder->where("order_number='".$order_number."'")->find();
		$orderInfo['order_amount_pay']=$orderInfo['order_price'];
		$status=$orderInfo->getData('status');
	}

	//如果订单是库存支付
	if(!empty($orderInfo) && $orderInfo->getData('paystyle')==3 && $status==7) {

		//获取代理商信息
		$agentInfo=$agent->find($orderInfo['agent_id']);

		//事务开始
		Db::startTrans();//开启事务
		try{

			//记录增加库存日志
			//写库存变动记录
			$ndata=array();
			$ndata['agent_id']=$orderInfo['agent_id'];
			$ndata['create_time']=time();
			$ndata['change_before']=$agentInfo['stock_money'];
			$ndata['money']=$orderInfo['order_amount_pay'];
			$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
			$ndata['change_type']=4;//加库存
			$ndata['account_type']=5;//其他

			$ndata['audit_time']=time();//审核时间
			$ndata['status']=2;//已审核
			$ndata['order_number']=$orderInfo['order_number'];
			$type==1 && $ndata['remark']='商品库存支付退款加库存';
			$type==2 && $ndata['remark']='礼包库存支付退款加库存';
			$resStock=$stock->save($ndata);


			//增加库存
			$agent->where('agent_id',$orderInfo['agent_id'])->setInc('stock_money',$orderInfo['order_amount_pay']);

			//变更支付状态
			$odata=array();
	 		$odata['refund_time']=time();//设置时间

			$type==1 && $resOrder=$order->save($odata,array('order_number'=>$order_number));
			$type==2 && $resOrder=$giftOrder->save($odata,array('order_number'=>$order_number));

			if($resOrder && $resStock) {
				$result=1;
			}

			// 提交事务
			Db::commit();

		}catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
		}


	}

	return $result;

}


//订单改价-生成新的订单号,参数：旧的订单号，新的价格,操作人
function agent_order_change_price($order_number,$price,$operator)
{
	//思路整理
	//获取订单信息
	//改以后的价格
	//存到库里
	//然后变更原来订单表的订单号

	$result=0;

	$order=model('admin/Agentorders');
	$changePrice=model('admin/Agentorderchangeprice');

	$orderInfo=$order->where("order_number='".$order_number."'")->find();

	//获取原订单表的ID串，此标志终身唯一
	$agent_order_ids=$orderInfo['id'];
	$orderCount=$order->where("order_number='".$order_number."'")->count();
	if($orderCount>0) {
		$orderList=$order->where("order_number='".$order_number."'")->select('id');
		foreach ($orderList as $k=>$v) {
			$tmp[]=$v['id'];
		}
		!empty($tmp) && $agent_order_ids=implode(',',$tmp);
	}

	$data=array();
	$data['create_time']=time();
	$data['operator']=$operator;

	$data['new_order_number']=$order->getOrderNumber($orderInfo['agent_id']);
	$data['new_price']=$price;

	$data['original_order_number']=$orderInfo['order_number'];
	$data['original_price']=$orderInfo['order_amount_pay'];
	$data['agent_order_ids']=$agent_order_ids;

	$res=$changePrice->save($data);
	$res && $result=$data['new_order_number'];

	return $result;
}

/**
 * 城市数据集降维处理返回string
 *
 * @param $array array [[0=>'北京'],[0=>'河北省'],...,[0=>'***']
 * @return string '北京,河北省,...,***'
 */
function array_reduction_for_city(&$str, $arr)
{
	return trim($str . ',' . $arr[0],',');
}


/**
 * //取消订单的库存和销量处理
 * @param string $order_number
 * @param number $type 1是商品 2是大礼包
 */
function cancel_order_sales_and_stock($order_number,$type=1)
{
	$result=0;

	//没有订单号直接返回
	if(empty($order_number)) {
		return $result;
	}

	$order=model('admin/Agentorders');
	$giftOrder=model('admin/Promotiongiftorder');
	$product=model('admin/Productmanagement');
	$gift=model('admin/Promotiongift');


	//针对商品订单
	if($type==1) {

		//校验订单状态 是不是已取消
		$orderInfo=$order->where("order_number='".$order_number."'")->find();


		if($orderInfo->getData('order_status')==7) {

			//先获取订单中的商品，订单中可能有多个商品
			$productList=$order->where("order_number='".$order_number."'")->field('pid,pnumber')->select();
			foreach($productList as $k=>$v) {
				//减销量加库存
				$product->where('id='.$v['pid'])->setDec('sales_volume',$v['pnumber']);
				$product->where('id='.$v['pid'])->setInc('inventory',$v ['pnumber']);

			}
			$result=1;
		}
	}


	//针对礼包订单
	if($type==2) {

		//校验订单状态 是不是已取消
		$orderInfo=$giftOrder->where("order_number='".$order_number."'")->find();

		if($orderInfo->getData('status')==7) {

			//先获取礼包订单中的商品
			$giftList=$giftOrder->where("order_number='".$order_number."'")->field('gift_id,pnumber')->select();

			foreach($giftList as $k=>$v) {
				//减销量加库存
				$gift->where('id='.$v['gift_id'])->setDec('sale',$v['pnumber']);
				$gift->where('id='.$v['gift_id'])->setInc('number',$v ['pnumber']);
			}
			$result=1;
		}
	}

	return $result;
}


/**
 * 获取快递信息的详情
 * @param str $order_number
 * @param int $type=1  1是商品订单  2是礼包订单
 */
function get_express_info($order_number,$type=1)
{

	$result=0;

	$appKey=config('web.express_key');

	$deliveryInfo=array();

	//礼包订单的
	if($type==2) {
		$giftOrder=model('admin/Promotiongiftorder');
		$deliveryInfo=$giftOrder->where(array('order_number'=>$order_number))->find();
	}

	//商品订单的
	if($type==1) {
		$delivery=model('admin/Agentorderdelivery');
		$deliveryInfo=$delivery->where(array('order_number'=>$order_number))->find();
	}


	//如果公司编号和物流单号，那么开始查询
	if(isset($deliveryInfo['express_code']) && $deliveryInfo['express_number']) {
		//$express_url='http://www.kuaidi100.com/applyurl?key='.$appKey.'&com='.$deliveryInfo->getData('express_code').'&nu='.$deliveryInfo['express_number'];
		$express_url='http://api.kuaidi100.com/api?id='.$appKey.'&com='.$deliveryInfo->getData('express_code').'&nu='.trim($deliveryInfo['express_number']).'&show=0&muti=1&order=desc';

		$get_content= http_curl_get($express_url);
		$data=json_decode($get_content);

		if($data->status!=1) {
			$result=0;
		} else {
			$result=$data;
		}

	}

	return $result;

}

//curl方式获取数据
function http_curl_get($url)
{
	$result="";

	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $url);
	curl_setopt ($curl, CURLOPT_HEADER,0);
	curl_setopt ($curl, CURLOPT_RETURNTRANSFER,1);
	curl_setopt ($curl,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
	curl_setopt ($curl, CURLOPT_TIMEOUT,5);
	$result = curl_exec($curl);
	curl_close ($curl);

	return $result;

}


/**
购买首单产品，自动变更为最低等级的身份
 */
function buy_first_order_product_change_role($order_number)
{
	$result=0;

	$order=model('admin/Agentorders');
	$agent=model('admin/Agents');
	$level=model('admin/Agentlevel');

	//获取订单信息
	$orderInfo=$order->where("order_number='".$order_number."'")->find();
	$agentInfo=$agent->find($orderInfo['agent_id']);

	//获取最低等级的角色
	$roleInfo=$level->getLastRoleInfo();


	if($roleInfo['id']>$agentInfo['role']) {

		$apply=model('admin/AgentApplications');//插入一条申请记录
		$ndata=array();
		$ndata['a_id']=$agentInfo['agent_id'];
		$ndata['type']=5;
		$ndata['create_ctime']=date('Y-m-d H:i:s');
		$ndata['target']=$roleInfo['id'];
		$ndata['status']=1;
		$ndata['examiner']=session('username');
		$ndata['examine_etime']=date('Y-m-d H:i:s');
		$ndata['remarks']='购买首单产品升级';
		$apply->save($ndata);

		//然后更改用户的等级
		$agent->where(['agent_id'=>$agentInfo['agent_id']])->update(['is_use'=>1,'role'=>$roleInfo['id'],'status'=>3]);

		//查询是否有申请记录，如果有就驳回
		if(in_array($agentInfo['status'],array(1,2))) {
			$apply->where(['a_id'=>$agentInfo['agent_id'],'status'=>0])->update(['status'=>3,'examine_etime'=>date("Y-m-d H:i:s"),'remarks'=>'购买 首单产品升级','examiner'=>session('username')]);
		}

		message_notification(1,$agentInfo['agent_id'],array('name'=>'首单产品','remark'=>'您购买首单产品，恭喜您已升级为'.$roleInfo['name'].'，请重新登录获取新的身份'));
	}
}


//根据代理商的等级，获取代理商享受的折扣价格
/*
 * $pid  商品ID
 * $number 数量
 * $role 角色
 */
function get_agent_discount_price_by_role($pid,$number,$role)
{
	$result=0;

	if(empty($pid)){
		return 0;
	}
	
	$agency=model('admin/Agentrewardagency');
	$product=model('admin/Productmanagement');
	
	$productInfo=$product->find($pid);
	
	$agencyInfo=$agency->where('role='.$role)->find();

	if(!empty($agencyInfo) && !empty($productInfo)) {
		//如果是等级商品，那么原价
		if($productInfo['is_agent_level']>0) {
			$result=round(($productInfo['sales_price']*$number),2);
		} else {
			$result=round($productInfo['sales_price']*$number*(1-$agencyInfo['ratio']/100),2);
		}
	}  else {
		$result=round(($productInfo['sales_price']*$number),2);
	}

	return $result;

}


//代理自我销售的奖励 type=1是进入数据库  2是显示
//20180917 修改  只获取收益不减库存
function agent_reward_self_sale($orderNumber,$type=1)
{
	$res=0;
	$finalResult=array();

	//根据订单号 获取订单信息
	$orders=model('admin/Agentorders');
	$orderInfo=$orders->where("order_number='".$orderNumber."'")->find();

	//先获取代理商的等级
	$agent=model('admin/Agents');
	$userInfo=$agent->find($orderInfo['agent_id']);

	$agency=model('admin/Agentrewardagency');

	//如果是意向代理，那么直接收益归直属上级,如果没有，那么直接返回
	if(empty($userInfo['inviter'])) {
		return $res;
	}

	//获取信息
	$inviterInfo=$agent->find($userInfo['inviter']);

	//是否继续
	$cando=0;
	if($inviterInfo['role']>$userInfo['role']) {
		$cando=1;
	}

	//代理折扣奖励是否启用
	$config=model('admin/Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();

	//然后根据等级，查找上级等级是否启用
	$level=model('admin/Agentlevel');
	$levelInfo=$level->find($inviterInfo['role']);

	//引入库存模型
	$stock=model('admin/Agentstockchange');


	//如果启用那么商品里面的，寻找对应的上级代理商的奖励系数
	if($levelInfo['valid']==1 && $configInfo['valid_agency_reward']==1 && $cando==1)
	{

		$reward=model('admin/Productagentreward');
		$systemReward=model('admin/Agentrewardagency');
		$orderReward=model('admin/Agentorderreward');

		$productList=$orders->where("order_number='".$orderNumber."'")->select();//获取订单的商品列表

		foreach($productList as $k=>$v)
		{

			$rewardInfo=$rewardInfo['agent_reward']=0;
			$rewardInfo=$reward->where(array('role'=>$inviterInfo['role'],'product_id'=>$v['pid']))->find();
	 

			//查找下，系统中设置的奖励系数
			$systemRewardInfo=$systemRewardInfo['ratio']=0;
			$systemRewardInfo=$systemReward->where(array('role'=>$inviterInfo['role']))->find();
 

			//找到了商品中对应的系数，那么直接乘以
			if(!empty($rewardInfo) && $rewardInfo['agent_reward'] )
			{
				$finalReward=round($v['ptotal_price_pay']*$rewardInfo['agent_reward']/100,2);
				//$minusStock=round($v['ptotal_price_pay']*(1-($rewardInfo['agent_reward'])/100),2);

			} else {

				//如果没有奖励 直接返回
				if(empty($systemRewardInfo['ratio'])) {
					return $res;
				}

				//用系统的进行计算
				$finalReward=round($v['ptotal_price_pay']*$systemRewardInfo['ratio']/100,2);
			}


			//判断是否已经存过了，如果有记录是update
			$count=$orderReward->where("order_number='".$orderNumber."' and product_id=".$v['pid'].' and reward_type=4')->count();
	 
			if($count==0 && $finalReward>0)
			{
				 
				//存到库里,订单记录表
				$data=array();
				$data['agent_id']=$inviterInfo['agent_id'];
				$data['create_time']=time();
				$data['directsale_reward']=0;
				$data['selfsale_reward']=$finalReward;
				$data['sales_amount']=$v['ptotal_price_pay'];
				$data['wholesale_reward']=0;
				$data['recommend_reward']=0;
				$data['reward_type']=4;
				$data['product_id']=$v['pid'];
				$data['status']=1;
				$data['order_number']=$orderNumber;
				$data['remark']='代理直接收益';

				if($type==1 && $data['selfsale_reward']>0) {
					//tp的BUG 第二次开始要手工指定主键ID
					//isset($orderReward->id) && $data['id']=$orderReward->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$res=$orderReward->isUpdate(false)->data($data, true)->save();
				}

				$type==2 && $finalResult[]=$data;
			}

		}

		//如果是2，那么返回数组
		if($type==2){
			return $finalResult;
		}
	}

	return $res;

}

/**
 * 计算商品运费
 * @param  array $order ['express'=>运费模板,'price'=>商品单价,'weight'=>商品重量,'num'=>购买数量,'province'=>收货省,'city'=>收货市,'area'=>收货区]
 * @return float        运费金额
 */
function countGoodsExpress($goods)
{
	$money = 0;
	import('Templete',EXTEND_PATH);
	$m_express_templete = model('admin/Expresstemplete');
	$templete = $m_express_templete->where(['id'=>$goods['express']])->find();// 通过运费模板获取运费规则
	if($templete)
	{
		$templete['rule_ids'] = explode(',', $templete['express_rule_ids']);
		unset($templete['express_rule_ids']);
	    $templeteObj = new \Templete($templete);
	    $money = $templeteObj->single($goods);
	}
    return $money;
}

/**
 * 计算订单中所有商品运费和
 * @param  array $order ['a_id'=>用户ID,'aid'=>收获地址ID,'goods'=>[]]
 * @return [type]        [description]
 */
function countOrderExpress($order)
{
	$money    = 0;// 运费
    $user     = $order['a_id'];
    $address  = $order['aid'];
    $goods    = $order['goods'];
    $add_data = model('index/AgentAddress')->where(['a_id'=>$user,'id'=>$address])->find();
    if($add_data)
    {
    	$cache_money = 0;// 临时运费
	    foreach ($goods as $key => &$val)
	    {
	        $val['province'] = $add_data->province;
	        $val['city']     = $add_data->city;
	        $val['area']     = $add_data->area;
	        $cache_money = countGoodsExpress($val);
	        if($cache_money < 0)
	        {
	        	$money = -1;
	        	break;
	        }
	        $money += countGoodsExpress($val);// 运费总计
	    }
    }
    return $money;
}

/**
 * 2018-07-20 CYL 检查收获地址信息是否完整
 *
 * @return Boolean
 */
function check_address_is_completed($address_data)
{
	$return = true;
	if (is_array($address_data))
	{
		foreach ($address_data as $key => $val)
		{
			if(in_array($key,['name','phone','province','city','area','address']) && empty($val))
			{
				$return = false;
			}
		}
	}else{
		$return = false;
	}
	return $return;
}

//订单支付完成后以后 给获取收益的人 设置提醒 减库存
function send_weixin_message_by_order_number($order_number)
{
	$order=model('index/Orders');
	$agents=model('index/Agents');


	//先校验订单状态，只有付款完毕，待发货的才可以继续
	$orderInfo=$order->where("order_number='".$order_number."'")->find();
 	$agentInfo=$agents->find($orderInfo['agent_id']);

	if($orderInfo->getData('order_status')==2) {
		//间接奖励
		$wholeRewardList=agent_reward_whole_sale($order_number,2);

		if(!empty($wholeRewardList)) {

			foreach($wholeRewardList as $k=>$v) {

				$tmp=$agents->find($v['agent_id']);

				$mdata=array();
				$mdata['first']='待到账收益及查看库存';
				$mdata['account']=$tmp['phone'];
				$mdata['time']=date('Y-m-d H:i:s');
				$mdata['type']='待到账收益';
				$mdata['remark']=$agentInfo['nickname']."支付完成，待到账收益".$v['wholesale_reward']."元，确认收货后即可到账，为了不影响您的收益到账，请及时查看库存余额确保充足";

				//给下单人发送发货通知
				message_notification(3,$v['agent_id'],$mdata);
			}
		}
	}


}

//低级别代理直接申请升级，公司已收到钱
//20180905此函数废弃
function lower_agent_direct_raise($applyId)
{
	//思路整理
	//直属推荐的，增加下级的库存，减直属上级的库存，增加直属上级的收益，以此类推
	$agent_id=$targetRole=$money=$result=0;

	$agent=model("admin/Agents");
	$agency=model('admin/Agentrewardagency');
	$stock=model('admin/Agentstockchange');
	$profit=model('admin/Agentprofit');
	$change=model('admin/Agentchangerole');
	$apply=model('admin/AgentApplications');
	$orderReward=model('admin/Agentorderreward');

	//获取申请信息
	$applyInfo=$apply->find($applyId);
	if(!empty($applyInfo)) {
		$agent_id=$applyInfo['a_id'];
		$targetRole=$applyInfo['target'];
		$money=$applyInfo['money'];
	}

	$final_user_ary=array();//可以奖励的用户信息


	//先查找是否有上级
	$userInfo=$agent->find($agent_id);

	//当前用户和目前等级校验 如果不大于那么不执行  或者钱数少于0 直接返回
	if($userInfo['role']>=$targetRole ||$money<=0){
		return 0;
	}

	if($userInfo['inviter'])
	{
		Db::startTrans();//开启事务

		try{


			$inviteInfo=$agent->find($userInfo['inviter']);//获取上级信息
			$LevelInfo=$agency->getLevelInfo($inviteInfo['role']);//获取上级的等级的奖励系数

			//如果用户的库存大于要减得额度，并且用户的库存比最低库存还高 那么继续
			if($inviteInfo['stock_money']>=$money && $inviteInfo['stock_money']>$LevelInfo['lowest_limit'])
			{

				//获取最终可以得到收益的用户和相关信息
				$final_user_ary=get_up_users_by_agent_id($agent_id,$money);

	 			if(!empty($final_user_ary))
	 			{
					foreach($final_user_ary as $kk=>$vv)
					{
						//设置是否可以减
						$canDo=0;
						//判断下库存够不够减
						$vv['stock_money']-$vv['minusStock']>$vv['lowest_limit'] && $canDo=1;


						if(!isset($vv['ratioDiff'])) continue;

						//如果减完之后 大于当前等级最低的库存额度，那么可以执行
						if(($canDo>0 && $vv['ratioDiff']>0))
						{
							//获取变更之前的库存额
							$agentInfo=$agent->find($vv['agent_id']);


							//写库存变动记录
							$ndata=array();
							$ndata['agent_id']=$vv['agent_id'];
							$ndata['create_time']=time();
							$ndata['change_before']=$agentInfo['stock_money'];
							$ndata['money']=$vv['minusStock'];
							$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
							$ndata['change_type']=5;//减库存
							$ndata['account_type']=5;//用户升级
							$ndata['product_id']=0;//其他

							$ndata['audit_time']=time();
							$ndata['status']=2;//已审核
							$ndata['remark']='库存充值减库存';


							$res=$stock->isUpdate(false)->data($ndata, true)->save();

							//写入变动记录成功，减库存
							if($res>0) {
								$result=$agent->where('agent_id',$vv['agent_id'])->setDec('stock_money',$vv['minusStock']);
							}

							//写订单奖励表
							$data=array();
							$data['create_time']=time();
							$data['agent_id']=$vv['agent_id'];
							$data['product_id']=0;
							$data['order_number']=0;
							$data['charge_reward']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],1);
							$data['sales_amount']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],2);
							$data['directsale_reward']=0;
							$data['recommend_reward']=0;
							$data['reward_type']=5;
							$data['status']=2;//设置成已发放
							$data['update_time']=time();
							$data['remark']='下级代理库存充值奖励';

							$res=$orderReward->isUpdate(false)->data($data, true)->save();


							//添加到代理收益记录表
							$data=array();
							$data['agent_id']=$vv['agent_id'];
							$data['create_time']=time();
							$data['profit']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],1);
							$data['sales_amount']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],2);
							$data['type']=7;//下级升级奖励
							$data['son_type']=1;//库存充值奖励-库存充值
							$data['relation_id']=$applyId;

							$res=$profit->isUpdate(false)->data($data, true)->save();

							//代理账户进行增加
							$result=$agent->where('agent_id',$vv['agent_id'])->setInc('profit',$data['sales_amount']);

						}
					}
				}
			}

			//执行事务
			Db::commit();
		} catch (\Exception $e) {

			//回滚事务
			Db::rollback();
		}

	}

	//都执行完毕了,申请的用户，加库存升等级
	//加入到库存变动记录
	//写库存变动记录
	$data=array();
	$data['agent_id']=$agent_id;
	$data['create_time']=time();
	$data['change_before']=$userInfo['stock_money'];
	$data['money']=$money;
	$data['change_after']=round($data['change_before']+$data['money'],2);
	$data['change_type']=6;//用户升级加库存
	$data['account_type']=5;//其他
	$data['product_id']=0;//其他

	$data['audit_time']=time();
	$data['status']=2;//已审核
	$data['remark']='用户升级充值库存';
	$res=$stock->isUpdate(false)->data($data, true)->save();

	//写入变动记录成功，//加库存
	if($res>0) {
		$result=$agent->where('agent_id',$agent_id)->setInc('stock_money',$money);

		//等级变动记录
		$data=array();
		$data['create_time']=time();
		$data['agent_id']=$agent_id;
		$data['before_role']=$userInfo['role'];
		$data['after_role']=$targetRole;
		$data['type']=2;
		$data['remark']="申请升级";
		$res=$change->save($data);

		//变等级
		$result=model('Agents')->where('agent_id',$agent_id)->update(['role'=> $targetRole,'is_use'=>1,'status'=>3]);
	}



	return $result;
}

//根据代理商ID，获取有收益的用户列表
function get_up_users_by_agent_id($agent_id,$money)
{
	$finalUserAry=array();

	$agent=model("admin/Agents");
	$agency=model('admin/Agentrewardagency');

	$userInfo=$agent->find($agent_id);

	//根据用户信息 获取用户的所有上级
	$parentIdStr=$userInfo['family'];
	if(!empty($parentIdStr))
	{
		$middelUserAry=$RoleAry=array();//中间用户数组
		$parentIdAry=explode(',',$parentIdStr);
		$parentIdAry=array_reverse($parentIdAry);

		//循环自己的上级，把比自己等级高的放到一个数组
		$i=0;
		foreach($parentIdAry as $k=>$v)
		{
			$tmpUserInfo=$agent->find($v);//获取用户信息
			$tmpLevelInfo=$agency->getLevelInfo($tmpUserInfo['role']);//获取角色对应的奖励系数

			if($tmpUserInfo['role']>$userInfo['role']) {
				$middelUserAry[$i]['agent_id']=$v;
				$middelUserAry[$i]['role']=$tmpUserInfo['role'];
				$middelUserAry[$i]['ratio']=$tmpLevelInfo['ratio'];


				$middelUserAry[$i]['lowest_limit']=$tmpLevelInfo['lowest_limit'];
				$middelUserAry[$i]['stock_money']=$tmpUserInfo['stock_money'];
				$RoleAry[$i]=$tmpUserInfo['role'];
			}
			$i++;
		}


		//获取平级就近原则的数据，把角色放到一个数组里面，然后统计各角色的数据，如果大于1那么获取就近的数据
		$tmp=array_count_values($RoleAry);
		foreach($tmp as $k=>$v) {
			if($v>1) {
				//说明出现的次数大于1，切知道是哪个角色，根据就近原则，第一个保留，然后的删除
				$roleCount=0;
				foreach($middelUserAry as $kk=>$vv) {
					if($k==$vv['role']) {
						if($roleCount>0){
							unset($middelUserAry[$kk]);
						}
						$roleCount++;
					}
				}
			}
		}

		//重新组织数组
		$middelUserAry=array_slice($middelUserAry,0);

		if(!empty($middelUserAry))
		{
			//为了让所有的等级都可以计算，那么对等级进行从低到高进行排序
			//$middelUserAry=array_sequence($middelUserAry,'role','SORT_ASC');

			//把比自己等级高的数组开始循环
			foreach($middelUserAry as $k=>$v)
			{
				//循环判断 获取比例的差级
				$finalUserAry[$k]=$v;
				if($k==0) {

					$realRole=$userInfo['role'];//用于计算比例差的角色等级

					$userLevelInfo=$agency->getLevelInfo($realRole);
					$ratio=$userLevelInfo['ratio'];//获取比例差

					$tmp=$v['ratio'];//邀请人
					$tmp>0 && $finalUserAry[$k]['ratioDiff']=$tmp;

				} else {

					$tmp=$v['ratio']-$middelUserAry[$k-1]['ratio'];//比例差
					$tmp>0 && $finalUserAry[$k]['ratioDiff']=$tmp;

				}

				$finalUserAry[$k]['minusStock']=round($money*(1-($v['ratio']/100)),2);//减的库存

			}
		}
	}

	return $finalUserAry;
}


//高级别提低级别申请升级，公司没收到钱 20180925 此函数废弃
function lower_agent_indirect_raise($applyId)
{
	//只能直属推荐
	$agent_id=$targetRole=$money=$minusStock=$result=0;

	$agent=model("admin/Agents");
	$stock=model('admin/Agentstockchange');
	$change=model('admin/Agentchangerole');
	$apply=model('admin/AgentApplications');
	$agency=model('admin/Agentrewardagency');


	//获取申请信息
	$applyInfo=$apply->find($applyId);
	if(!empty($applyInfo)) {
		$agent_id=$applyInfo['a_id'];
		$targetRole=$applyInfo['target'];
		$money=$applyInfo['money'];
	}

	//先查找是否有上级
	$userInfo=$agent->find($agent_id);
	$userLevelInfo=$agency->getLevelInfo($userInfo['role']);

	//当前用户和目前等级校验 如果不大于那么不执行  或者钱数少于0 直接返回
	if($userInfo['role']>=$targetRole ||$money<=0){
		return 0;
	}

	//减上级库存
	if($userInfo['inviter']) {
		$inviterInfo=$agent->find($userInfo['inviter']);
		$inviterLevelInfo=$agency->getLevelInfo($inviterInfo['role']);

		//减得库存，直接计算
		if(!empty($inviterLevelInfo)) {
			$minusStock=round($money*(1-($inviterLevelInfo['ratio']/100)),2);
		}


		//校验下上级的库存是否充足
		if($inviterInfo['stock_money']<$minusStock) {
			return $result;
		}

		Db::startTrans();//开启事务

		try{

			//写库存变动记录
			$ndata=array();
			$ndata['agent_id']=$inviterInfo['agent_id'];
			$ndata['create_time']=time();
			$ndata['change_before']=$inviterInfo['stock_money'];
			$ndata['money']=$minusStock;
			$ndata['change_after']=round($ndata['change_before']-$ndata['money'],2);
			$ndata['change_type']=5;//库存充值减库存
			$ndata['account_type']=5;//用户升级
			$ndata['product_id']=0;//其他

			$ndata['audit_time']=time();
			$ndata['status']=2;//已审核
			$ndata['remark']='库存充值减库存';

			$res=$stock->isUpdate(false)->data($ndata, true)->save();

			//写入变动记录成功，减库存
			if($res>0) {
				$result=$agent->where('agent_id',$inviterInfo['agent_id'])->setDec('stock_money',$minusStock);
			}


			//写代理人的库存变动记录
			$data=array();
			$data['agent_id']=$agent_id;
			$data['create_time']=time();
			$data['change_before']=$userInfo['stock_money'];
			$data['money']=$money;
			$data['change_after']=round($data['change_before']+$data['money'],2);
			$data['change_type']=6;//用户升级充值库存
			$data['account_type']=5;//其他
			$data['product_id']=0;//其他

			$data['audit_time']=time();
			$data['status']=2;//已审核
			$data['remark']='用户升级充值库存';
			$res=$stock->isUpdate(false)->data($data, true)->save();

			//写入变动记录成功，//加库存
			if($res>0) {
				$result=$agent->where('agent_id',$agent_id)->setInc('stock_money',$money);

				//等级变动记录
				$data=array();
				$data['create_time']=time();
				$data['agent_id']=$agent_id;
				$data['before_role']=$userInfo['role'];
				$data['after_role']=$targetRole;
				$data['reason']=2;
				$data['remark']="申请升级";
				$res=$change->save($data);

				//变等级
				$result=model('Agents')->where('agent_id',$agent_id)->update(['role'=> $targetRole,'is_use'=>1,'status'=>3]);
			}

			//执行事务
			Db::commit();
		} catch (\Exception $e) {

			//回滚事务
			Db::rollback();
		}

	}

	return $result;


}

//根据订单号中的商品，升级用户等级
function up_user_level_by_ordernumber($order_number)
{
	$result=0;

	//思路整理，获取商品信息
	//查找商品中是否有升级的商品
	//如果有，那么看用户的等级，是否是会员，如果是那么升级，并且来一条记录
	$order=model('admin/Agentorders');
	$agent=model('admin/Agents');
	$change=model('admin/Agentchangerole');
	$product=model('admin/Productmanagement');
	$level=model('admin/Agentlevel');
	$stock=model('admin/Agentstockchange');

	//先获取订单信息，然后获取用户信息
	$orderInfo=$order->where("order_number='".$order_number."'")->find();

	if(!empty($order)) {
		$agentInfo=$agent->find($orderInfo['agent_id']);

		//根据商品ID，获取商品信息
		$productInfo=$product->find($orderInfo['pid']);
		if($productInfo['is_agent_level']>0 && $productInfo['is_agent_level']>$agentInfo['role']) {

			//等级变更记录
			$data=array();
			$data['create_time']=time();
			$data['agent_id']=$agentInfo['agent_id'];
			$data['before_role']=$agentInfo['role'];
			$data['after_role']=$productInfo['is_agent_level'];
			$data['type']=2;
			$data['remark']="购买商品升级,商品ID".$productInfo['id'];
			$res=$change->save($data);

			//变等级
			$result=$agent->where('agent_id',$agentInfo['agent_id'])->update(['role'=>$productInfo['is_agent_level'],'is_use'=>1,'status'=>3]);

			//写代理人的库存变动记录
			$data=array();
			$data['agent_id']=$orderInfo['agent_id'];
			$data['create_time']=time();
			$data['change_before']=$agentInfo['stock_money'];
			$data['money']=$orderInfo['order_amount_pay'];
			$data['change_after']=round($data['change_before']+$data['money'],2);
			$data['change_type']=8;//购买商品充值库存
			$data['account_type']=5;//其他
			$data['product_id']=$orderInfo['pid'];//其他
			
			$data['audit_time']=time();
			$data['status']=2;//已审核
			$data['remark']='购买商品充值库存';
			$res=$stock->save($data);
			
			$result=$agent->where('agent_id',$orderInfo['agent_id'])->setInc('stock_money',$data['money']);
			
			
			//带等级的商品订单  直接设置订单完成  并且发放奖励
			$data=array();
			$data['order_status']=4;
			$data['commplete_time']=time();
			$res=$order->save($data,array('order_number'=>$order_number));
			
			//发个通知吧
			message_notification(1,$agentInfo['agent_id'],array('name'=>'购买商品','remark'=>'您购买商品产品，恭喜您已升级为'.get_reward_levelname($productInfo['is_agent_level']).'，请重新登录获取新的身份'));

			//sleep一秒，然后发放奖励
			sleep(1);
			//奖励发放
			provide_order_reward($order_number);
		}
	}

	return $result;
}



//代理招商奖励-非订单适用
//适用库存充值，代理升级充值库存  2=库存充值   1代理升级库存充值
//20180917继续启用
function agent_reward_recommend_no_ordernumber($relation_id,$type)
{
	/*
	 * 根据代理的ID，寻找上级ID，然后根据的等级，去寻找开启了几级，然后根据系数，计算收益
	*/
	$res=0;
	$finalResult=$relationInfo=array();

	$app=model('index/AgentApplications');
	$stockchange=model('index/Agentstockchange');

	//申请升级的时候充值库存
	if($type==1) {
		$relationInfo=$app->find($relation_id);
		!empty($relationInfo['a_id']) && $relationInfo['agent_id']=$relationInfo['a_id'];
	}

	//在线支付充值库存
	if($type==2) {
		$relationInfo=$stockchange->find($relation_id);
	}

	//先获取用户当前的信息
	$agent=model('Agents');
	$userInfo=$agent->find($relationInfo['agent_id']);

	//如果是意向代理，那么招商收益使用他上级的等级获取深度
	if($userInfo['role']==0 && !empty($userInfo['inviter'])) {
		$tmp=$agent->find($userInfo['inviter']);
		$userInfo['role']=$tmp['role'];
	}

	//根据用户的等级，获取招商奖励的深度层级
	$levelInfo=model('Agentlevel')->find($userInfo['role']);

	//招商奖励是否启用
	$config=model('admin/Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();

	//判断是否开启招商奖励，并且是否有上级，如果可以继续走
	if(!empty($userInfo['inviter']) && $configInfo['valid_recommend_reward']==1 && $levelInfo['deep']>0){

		//订单奖励表模型
		$orderReward=model('admin/Agentorderreward');

		//获取所有上级
		$parentIdStr=$userInfo['family'];
		if(!empty($parentIdStr))
		{
			//提取深度个数到数组
			$parentIdAry=explode(',',$parentIdStr);
			$parentIdAry=array_reverse($parentIdAry);//反转
			$realAry=array_slice($parentIdAry,0,$levelInfo['deep']);//从后往前取数据

			//循环查询招商奖励收益，然后存到奖励表
			foreach($realAry as $kk=>$vv)
			{
				$data=array();
				$tmpUserInfo=$agent->find($vv);
				$data['directsale_reward']=0;
				$data['wholesale_reward']=0;
				$data['recommend_reward']=get_agent_recommend_reward(0,$relationInfo['money'],$tmpUserInfo['role'],$kk+1);
				$data['recommend_hierarchy']=$kk+1;
				$data['sales_amount']=$relationInfo['money'];
				$data['create_time']=time();
				$data['agent_id']=$tmpUserInfo['agent_id'];
				$data['order_number']=0;
				$data['product_id']=0;
				$data['status']=2;//已结算
				$data['update_time']=time();
				$type==1 && $data['reward_type']=6;
				$type==2 && $data['reward_type']=7;
				$type==1 && $data['remark']='代理招商奖励-代理升级';
				$type==2 && $data['remark']='代理招商奖励-库存充值';
				$data['relation_id']=$relation_id;

				
				//招商奖励大于0
				if($data['recommend_reward']>0) {
					//数据保存
					$orderReward->isUpdate(false)->data($data, true)->save();

					//加载代理收益表模型
					$profit=model('Agentprofit');
					//插入到收益记录
					$ndata=array();
					$ndata['agent_id']=$tmpUserInfo['agent_id'];
					$ndata['create_time']=time();
					$ndata['profit']=$data['recommend_reward'];
					$ndata['sales_amount']=$relationInfo['money'];
					$ndata['type']=3;//招商奖励

					$type==1 && $ndata['son_type']=3;//招商奖励-下级升级
					$type==2 && $ndata['son_type']=4;//招商奖励-库存充值
					$ndata['relation_id']=$relation_id;

					$res=$profit->isUpdate(false)->data($ndata, true)->save();

					//代理账户进行增加
					$result=$agent->where('agent_id',$tmpUserInfo['agent_id'])->setInc('profit',$data['recommend_reward']);

				}

			}
		}
	}
	
	return $res;
}

//代理充值库存，产生的收益
//20180915算法变更，不在减库存
function agent_charge_stock_provide_profit($chargeId)
{
	//思路整理
	//增加直属上级的收益，以此类推
	$agent_id=$money=$result=0;

	$agent=model("admin/Agents");
	$agency=model('admin/Agentrewardagency');
	$stock=model('admin/Agentstockchange');
	$profit=model('admin/Agentprofit');
	$orderReward=model('admin/Agentorderreward');

	//获取申请信息
	$chargeInfo=$stock->find($chargeId);
	if(!empty($chargeInfo)) {
		$agent_id=$chargeInfo['agent_id'];
		$money=$chargeInfo['money'];
	}

	$final_user_ary=array();//可以奖励的用户信息


	//先查找是否有上级
	$userInfo=$agent->find($agent_id);


	if($userInfo['inviter'])
	{
		Db::startTrans();//开启事务

		try{

			$inviteInfo=$agent->find($userInfo['inviter']);//获取上级信息
			$LevelInfo=$agency->getLevelInfo($inviteInfo['role']);//获取上级的等级的奖励系数


			//获取最终可以得到收益的用户和相关信息
			$final_user_ary=get_up_users_by_agent_id($agent_id,$money);

			if(!empty($final_user_ary))
			{
				foreach($final_user_ary as $kk=>$vv)
				{

					if(!isset($vv['ratioDiff'])) continue;

					//如果减完之后 大于当前等级最低的库存额度，那么可以执行
					if(($vv['ratioDiff']>0))
					{
						//获取变更之前的库存额
						$agentInfo=$agent->find($vv['agent_id']);
 
						//写订单奖励表
						$data=array();
						$data['create_time']=time();
						$data['agent_id']=$vv['agent_id'];
						$data['product_id']=0;
						$data['order_number']=0;
						$data['charge_reward']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],1);
						$data['sales_amount']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],2);
						$data['directsale_reward']=0;
						$data['recommend_reward']=0;
						$data['reward_type']=8;
						$data['status']=2;//设置成已发放
						$data['update_time']=time();
						$data['remark']='下级库存充值奖励';
						$data['relation_id']=$chargeId;

						$res=$orderReward->isUpdate(false)->data($data, true)->save();


						//添加到代理收益记录表
						$data=array();
						$data['agent_id']=$vv['agent_id'];
						$data['create_time']=time();
						$data['profit']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],1);
						$data['sales_amount']=get_agent_agency_reward($money,$vv['ratioDiff'],$vv['minusStock'],2);
						$data['type']=8;//库存充值奖励
						$data['son_type']=2;//库存充值奖励-库存充值
						$data['relation_id']=$chargeId;

						$res=$profit->isUpdate(false)->data($data, true)->save();

						//代理账户进行增加
						$result=$agent->where('agent_id',$vv['agent_id'])->setInc('profit',$data['sales_amount']);

					}
				}
			}

			//执行事务
			Db::commit();
		} catch (\Exception $e) {

			//回滚事务
			Db::rollback();
		}

	}

	return $result;
}


//根据订单ID获取订单信息
function get_order_info_by_ordernumber($orderNumber,$field='*')
{
	$res='';
	$info=model('admin/Agentorders')->field($field)->where("order_number='".$orderNumber."'")->find();
	$res=$info;
	if($field!='*') {
		$res=$info[$field];
	}
	return $res;

}

//根据关联ID获取信息 1是 升级申请  2是 库存充值 3是转账
function get_info_by_relation_id($relationId,$type,$field='*')
{
	$res='';
	$app=model('index/AgentApplications');
	$stockchange=model('index/Agentstockchange');
	$transfer=model('index/Agentstocktransfer');

	if($type==1) {
		$info=$app->find($relationId);
	}

	if($type==2) {
		$info=$stockchange->find($relationId);
	}
	
	$type==3 && $info=$transfer->find($relationId);

	$res=$info;
	if($field!='*') {
		$res=$info[$field];
	}
	return $res;
}

//根据代理商的等级，获取代理商享受的扣减价格
/*
 * pid 商品ID
 * number 商品数量
 * role  角色
 */
function get_agent_decrease_price_by_role($pid,$number,$role)
{
	$result=0;

	if(empty($pid)){
		return 0;
	}
	
	$agency=model('admin/Agentrewardagency');
	$product=model('admin/Productmanagement');
	
	$productInfo=$product->find($pid);
	$agencyInfo=$agency->where('role='.$role)->find();

	if(!empty($agencyInfo) && !empty($productInfo)) {
		
		//如果是等级商品，那么原价
		if($productInfo['is_agent_level']>0) {
			
			$result=0;
			
		} else {
			$result=round($productInfo['sales_price']*$number*$agencyInfo['ratio']/100,2);
		}
		
	} 

	return $result;

}

/**
 * 累加订单数与销售额(仅适用店铺)
 *
 * @param int $a_id 下单人ID
 * @param array $pids 订单中的商品ID
 * @param float $money 订单总额
 */
function auto_accumulation_order_and_money($a_id,$pids,$money)
{
	/**
	 * 通过a_id获取所有上级代理ID
	 * 通过商品ID比对,是否有上级代理商正在上架的商品
	 * 更新所有包含该上架商品的上级代理商的店铺订单数和销售总额(总额按订单总额粗略累加)
	 */
	$m_agents    = model('index/Agents');
	$m_shopgoods = model('index/Shopgoods');
	$m_shop      = model('index/Shop');
	$info = $m_agents->where(['agent_id'=>$a_id])->find();// 下单人信息
	if(!empty($info['family']))
	{
		$superior_ids = [];// 要找的所有上级代理商
		$role_max = 0;// family中出现的最高身份等级
		$role_mid = 0;// 临时身份等级
		$superior = $m_agents->field('agent_id,role')->where(['agent_id'=>['in',$info['family']],'is_del'=>0])->order('family DESC')->select();// 倒序
		foreach ($superior as $key => $val)
		{
			if($val['role'] > $info['role'])
			{// 若该代理商身份高于下单人的身份
				$role_mid = $val['role'];
				if($role_mid > $role_max)
				{// 若该代理商身份高于目前的最高身份
					$role_max = $role_mid;
					$superior_ids[] = $val['agent_id'];// 由低到高的上级ID
				}
			}
		}
		if(!empty($superior_ids))
		{// 若有上级代理ID
			$superior_ids = $m_shopgoods->Distinct(true)->field('a_id')->where(['a_id'=>['in',implode(',', $superior_ids)],'p_id'=>['in',implode(',',$pids)],'type'=>1,'is_del'=>0])->column('a_id');// 查询这些上级的店铺中,是否有上架当前购买的商品
			if($superior_ids)
			{
				$m_shop->where(['a_id'=>['in',implode(',',$superior_ids)]])->setInc('orders',1);// 订单数增加
				$m_shop->where(['a_id'=>['in',implode(',',$superior_ids)]])->setInc('sale',$money);// 销售额增加
			}
		}
	}
}

//根据用户等级，或者等级对应的最低库存额度
function get_lowest_limit_by_role($role)
{
	$res=0;
	//$agency = model('admin/Agentrewardagency');
	//$res=(int) $agency->where('role='.$role)->value('lowest_limit');
	$array=array(
			1=>333,
			2=>1500,
			3=>6000,
			4=>30000,
	);
	$res=$array[$role];
	return $res;
}

//高等级代理替下级申请升级-不处理库存
function lower_agent_direct_raise_by_upper($applyId)
{

	//思路整理
	//直属推荐的，增加下级的库存，变更下级的等级，减直属上级的库存
	$agent_id=$targetRole=$money=$apply_by_id=$result=0;
	
	$agent=model("admin/Agents");
	$change=model('admin/Agentchangerole');
	$apply=model('admin/AgentApplications');
	
	//获取申请信息
	$applyInfo=$apply->find($applyId);
	if(!empty($applyInfo)) {
		$agent_id=$applyInfo['a_id'];
		$targetRole=$applyInfo['target'];
		$money=$applyInfo['money'];
		$apply_by_id=$applyInfo['apply_by_id'];
	}
	
	//先查找是否有上级
	$userInfo=$agent->find($agent_id);
	
	//当前用户和目前等级校验 如果不大于那么不执行  或者钱数少于0 直接返回
	if($userInfo['role']>=$targetRole || empty($apply_by_id)){
		return 0;
	}
	
	//等级变动记录
	$data=array();
	$data['create_time']=time();
	$data['agent_id']=$agent_id;
	$data['before_role']=$userInfo['role'];
	$data['after_role']=$targetRole;
	$data['type']=2;
	$data['remark']="申请升级";
	$res=$change->save($data);

	//变等级
	$result=model('Agents')->where('agent_id',$agent_id)->update(['role'=> $targetRole,'is_use'=>1,'status'=>3]);
	
	return $result;
}

//代理库存转账
function agent_stock_transfer($operater_agent_id,$agent_id,$money)
{
	$res=0;
	
	//思路整理  先加库存扣减记录，然后减操作人的库存，然后被操作人的库存增加记录，然后加库存，然后插入到库存转账记录表
	$agent=model('admin/Agents');
	$stock=model('admin/Agentstockchange');
	$transfer=model('admin/Agentstocktransfer');
	$agency=model('admin/Agentrewardagency');
	
	$operaterInfo=$agent->find($operater_agent_id);
	$agentInfo=$agent->find($agent_id);
	
	//根据操作人的等级，计算操作人需要减掉多少库存
	$LevelInfo=$agency->getLevelInfo($operaterInfo['role']);//获取角色对应的奖励系数
	$finalMoney=round($money*(100-$LevelInfo['ratio'])/100,2);
	
	
	//校验下 库存够不够减 如果够 那么继续
	if($operaterInfo['stock_money']>=$finalMoney) {
	
		Db::startTrans();//开启事务
		
		try{
			
			//申请人写库存变动记录
			$data=array();
			$data['agent_id']=$operater_agent_id;
			$data['create_time']=time();
			$data['change_before']=$operaterInfo['stock_money'];
			$data['money']=$finalMoney;
			$data['change_after']=round($data['change_before']-$data['money'],2);
			$data['change_type']=9;//给下级转账减库存
			$data['account_type']=5;//其他
			
			$data['audit_time']=time();
			$data['status']=2;//已审核
			$data['remark']='为下级代理'.$agentInfo['phone'].'转移库存,转移额度为:￥'.$finalMoney;
			$stock->isUpdate(false)->data($data, true)->save();
			
			//发个消息通知
			$mdata=array();
			$mdata['first']='库存减少';
			$mdata['account']=$operaterInfo['phone'];
			$mdata['time']=date('Y-m-d H:i:s');
			$mdata['type']='库存减少';
			$mdata['remark']='为下级代理'.$agentInfo['phone'].'转移库存,转移额度为:￥'.$finalMoney;
			message_notification(3,$operaterInfo['agent_id'],$mdata);
			
			//减库存
			$agent->where('agent_id',$operater_agent_id)->setDec('stock_money',$finalMoney);
			
			
			//下级库存增加记录
			$data=array();
			$data['agent_id']=$agent_id;
			$data['create_time']=time();
			$data['change_before']=$agentInfo['stock_money'];
			$data['money']=$money;
			$data['change_after']=round($data['change_before']+$data['money'],2);
			$data['change_type']=10;//上级转账加库存
			$data['account_type']=5;//其他
			
			$data['audit_time']=time();
			$data['status']=2;//已审核
			$data['remark']='上级代理'.$operaterInfo['phone'].'转移库存,转移额度为:￥'.$money;
			$res=$stock->isUpdate(false)->data($data, true)->save();
			
			//给下单人发送发货通知
			$mdata=array();
			$mdata['first']='库存增加';
			$mdata['account']=$agentInfo['phone'];
			$mdata['time']=date('Y-m-d H:i:s');
			$mdata['type']='库存增加';
			$mdata['remark']='上级代理'.$operaterInfo['phone'].'转移库存,转移额度为:￥'.$money;
			
			message_notification(3,$agent_id,$mdata);
			
			
			//下级加库存
			$agent->where('agent_id',$agent_id)->setInc('stock_money',$money);
			
			//写入转账记录
			$data=array();
			$data['operater_agent_id']=$operater_agent_id;
			$data['agent_id']=$agent_id;
			$data['money']=$money;
			$data['create_time']=time();
			$transfer->save($data);
		
			$res=$transfer->id;
			
			//执行事务
			Db::commit();
		} catch (\Exception $e) {
		
			//回滚事务
			Db::rollback();
		}
	}
	
	return $res;
	
}

//给下级转账产生的招商奖励2级，一个他自己，一个是上级
//20180914此函数废弃
function agent_reward_recommend_by_transfer($transferId)
{
	$res=0;
	
	$agent=model('admin/Agents');
	$transfer=model('admin/Agentstocktransfer');
	
	$transferInfo=$transfer->find($transferId);
	
	//招商奖励是否启用
	$config=model('admin/Agentrewardconfig');
	$configInfo=$config->order('id','desc')->find();
	
	if(!empty($transferInfo) && !empty($configInfo)) {
		
		$user_ary=array();
		//订单奖励表模型
		$orderReward=model('admin/Agentorderreward');
		//加载代理收益表模型
		$profit=model('admin/Agentprofit');
		
		
		//操作人信息获取
		$agent_id=$transferInfo['operater_agent_id'];
		$agentInfo=$agent->find($agent_id);
		
		//数据初始化
		$k=0;
		$user_ary[$k]['agent_id']=$agent_id;
		$user_ary[$k]['role']=$agentInfo['role'];
		$user_ary[$k]['hierarchy']=1;
		
		//如果有上级，那么第二度
		if(!empty($agentInfo['inviter'])) {
			$inviterInfo=$agent->find($agentInfo['inviter']);
			$user_ary[$k+1]['agent_id']=$inviterInfo['agent_id'];
			$user_ary[$k+1]['role']=$inviterInfo['role'];
			$user_ary[$k+1]['hierarchy']=2;
		}
 
		foreach($user_ary as $k=>$v){
			//加入订单奖励表
			$data=array();
			$data['recommend_reward']=get_agent_recommend_reward_by_money($transferInfo['money'],$v['role'],$v['hierarchy']);
			$data['recommend_hierarchy']=$v['hierarchy'];
			$data['create_time']=time();
			$data['agent_id']=$v['agent_id'];
			$data['relation_id']=$transferId;
			$data['reward_type']=9;
			$data['status']=2;
			$data['remark']='代理招商奖励-给下级转库存';
			
			//招商奖励大于0
			if($data['recommend_reward']>0) {
				//tp的BUG 第二次开始要手工指定主键ID
				isset($orderReward->id) && $data['id']=$orderReward->id+1;
				$orderReward->isUpdate(false)->save($data);
				
				//加入到收益表
				$data=array();
				$data['agent_id']=$v['agent_id'];
				$data['create_time']=time();
				$data['profit']=get_agent_recommend_reward_by_money($transferInfo['money'],$v['role'],$v['hierarchy']);
				$data['sales_amount']=$transferInfo['money'];
				$data['type']=3;//招商奖励
				$data['son_type']=5;//招商奖励-给下级转库存
				$data['relation_id']=$transferId;
					
				$res=$profit->isUpdate(false)->data($data, true)->save();
					
				//代理账户进行增加
				$result=$agent->where('agent_id',$v['agent_id'])->setInc('profit',$data['profit']);
			}
			
		}
		
	}
	
}

function get_agent_recommend_reward_by_money($money,$role,$hierarchy) 
{
	$res = 0;
	
	//系统招商奖励表模型
	$reward=model('admin/Agentrewardrecommend');
	
	
	//获取系统表的系数，然后相乘
	$rewardInfo=$reward->where(array('role'=>$role,'hierarchy'=>$hierarchy))->find();
	!empty($rewardInfo['value']) && $res=round($money*$rewardInfo['value']/100,2);
	
	return $res;
}