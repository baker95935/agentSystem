<?php

namespace app\index\model;

use think\Model;
use think\Db;

class Orders extends Model
{
	protected $table = 'agent_orders';

	//定义和商品表的关联
	public function product()
	{
		return $this->hasOne('Products','id','pid');
	}

	//定义和订单地址表的关联
	public function consignee()
	{
		return $this->hasOne('Consignee','id','consignee_address_id');
	}

	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}

	//定义和发货表的关联
	public function delivery()
	{
		return $this->hasOne('Delivery','id','delivery_id');
	}

	/**
	 * 获取某代理商(直销)销售总额
	 *
	 * @param $where array 限制条件
	 * @return int
	 */
	public function getAgentSalesMoney($where)
	{
		$result = $this->where($where)->sum('order_amount_pay');
		return $result;
	}

	/**
	 * 获取某代理商(直销)销售订单数
	 *
	 * @param $where array 限制条件
	 * @return int
	 */
	public function getAgentSalesOrderNum($where)
	{
		$result = $this->where($where)->count();
		return $result;
	}

	/**
	 * 添加订单
	 * @param int $userId
	 * @param int $addressId
	 * @param string $productIdStr
	 * @param string $productNumStr
	 * @param int $productType
	 * @package int $paystyle
	 * @package int $remark
	 * @param int $exemption_from_postage
	 */
	public function addOrder($userId,$addressId,$productIdStr,$productNumStr,$productType,$paystyle,$remark,$exemption_from_postage)
	{
		//模型获取
		$address = model('index/AgentAddress');
		$orders = model('index/Orders');
		$consigneeAddress=model('index/Consignee');
		$agents=model('index/Agents');

		//获取用户信息
		$agentInfo=$agents->find($userId);

		Db::startTrans();//开启事务
		try{
			// 2018-07-19 CYL 运费计算
			$express = [];

			//根据地址ID，获取地址信息，插入到订单收货表
			$addressInfo = $address->find($addressId);

			// 生成订单号
			$orderNumber = $orders->getOrderNumber($userId);

			//订单收货数据 存取
			$consigneeData = array();
			$consigneeData['consignee_name']  = $addressInfo['name'];
			$consigneeData['consignee_phone'] = $addressInfo['phone'];
			$consigneeData['province']        = $addressInfo['province'];
			$consigneeData['city']            = $addressInfo['city'];
			$consigneeData['area']            = $addressInfo['area'];
			$consigneeData['address']         = $addressInfo['address'];
			$consigneeData['agent_id']        = $userId;
			$consigneeData['order_number']    = $orderNumber;
			$consigneeData['create_time']     = time();
			$consigneeAddress->save($consigneeData);// 保存收货信息
			$consigneeId = $consigneeAddress->id;// 订单收货信息ID

			//获取购买的产品数量和种类
			$product = model('index/Products');
			$productIdAry  = explode(',', $productIdStr);
			$productNumAry = explode(',',$productNumStr);

 			//获取多条数据保存到订单表
			$data=array();
			$order_amount=$res=0;
			for($i=0;$i<$productType;$i++)
			{
				$data['pid']          = $productIdAry[$i];
				$tmpInfo = $product->find($data['pid']);

				$data['order_number']         = $orderNumber;
				$data['pname']                = $tmpInfo['product_name'];
				$data['pprice']               = $tmpInfo['sales_price'];
				$data['pnumber']              = $productNumAry[$i];
				$data['ptotal_price']         = round($productNumAry[$i]*$data['pprice'],2);
				$data['ptotal_price_pay']     = get_agent_discount_price_by_role($data['pid'],$productNumAry[$i],$agentInfo['role']);
				$data['consignee_address_id'] = $consigneeId;
				$data['create_time']          = time();
				$data['agent_id']             = $userId;

				$data['paystyle']             = $paystyle;
				$data['order_status']         = 2;
				
				$data['paystyle'] ==1 && $data['pay_time']=time();//如果是线下支付，那么设置支付时间为当前
				$data['paystyle'] ==2 && $data['order_status'] = 1;//如果是在线支付，那么状态为1
				$data['paystyle'] ==3 && $data['order_status'] = 1;//如果是库存支付，那么状态为1

				!empty($remark) && $data['agent_remark'] = $remark;

				isset($data['ptotal_price_pay']) && $order_amount += $data['ptotal_price_pay'];//获取订单价格

				// 2018-07-19 CYL 计算运费所需数据
				$express[] = [
					'express'  => $tmpInfo->express,
					'price'    => $tmpInfo->sales_price,
					'weight'   => $tmpInfo->weight,
					'num'      => $productNumAry[$i],
					'province' => $addressInfo['province'],
					'city'     => $addressInfo['city'],
					'area'     => $addressInfo['area'],
				];

				//校验下 是否有足够的库存
				if($tmpInfo['inventory']>=$data['pnumber']) {

					//产品表数量变更减库存
					$product->where('id='.$data['pid'])->setDec('inventory',$data['pnumber']);
					//产品表数量变更加销量
					$product->where('id='.$data['pid'])->setInc('sales_volume',$data['pnumber']);

					//校验下库存是否为0,0直接下架
					$productInfo=$product->find($data['pid']);
					if($productInfo['inventory']==0) {
						$ndata=array();
						$ndata['state']=0;
						$product->save($ndata,['id'=>$data['pid']]);
					}

					//tp的BUG 第二次开始要手工指定主键ID
					isset($orders->id) && $data['id']=$orders->id+1;
					/*同一个实例多次添加，需要设置isupdate*/
					$orders->isUpdate(false)->save($data);
 
					$res=1;
				}
			}
			if($res==1) {

				/* CYL 清除购物车记录 S */
				model('index/Cart')->where(['a_id'=>$userId,'pid'=>['in',$productIdStr]])->delete();
				/* CYL 清除购物车记录 E */

				//得到订单总额，根据订单总数计算运费
				$trans_expenses = 0;
				// 2018-07-19 CYL 计算所有商品运费和
				foreach ($express as $key => $val)
				{
					$trans_expenses += countGoodsExpress($val);
				}

				$exemption_from_postage==1 && $trans_expenses=0;// 包邮,运费为0

				//更新到订单表中,享受折扣价的处理
				$ndata = array();
				$ndata['trans_expenses']   = $trans_expenses;
				$ndata['order_amount']     = round($order_amount+$trans_expenses,2);
		        $ndata['order_amount_pay'] = round($order_amount+$trans_expenses,2);

				$orders->where('order_number', $orderNumber)->update($ndata);
			}

			// 提交事务
			Db::commit();

			return $orderNumber;

		}catch (\Exception $e) {
			// 回滚事务
			Db::rollback();

			return -1;
		}
	}

	//生成唯一的订单ID
	public	function getOrderNumber($userId)
	{
		return $userId.date('YmdHis');
	}
	
	//校验下现在价格和订单价格是否一致
	public function checkOrderNumberPriceChange($order_number,$order_amount_pay)
	{
		$res=0;
	
		$orderInfo=model('Orders')->where("order_number='".$order_number."'")->find();
	 
		if($order_amount_pay!=$orderInfo['order_amount_pay']) {
			$res=1;
		}
		return $res;
	}
}