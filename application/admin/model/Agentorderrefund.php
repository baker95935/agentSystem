<?php

namespace app\admin\model;

use think\Model;

class Agentorderrefund extends Model
{
	protected $table = 'agent_order_refund';
	
	//审核状态
	public $authStatus=array(
		1=>'未审核',
		2=>'审核通过',
	);
	
	//退款状态
	public $refundStatus=array(
		1=>'未退款',
		2=>'退款成功',
		3=>'退款失败',
	);
	
	//退款支付方式
	public $refundPayType=array(
		1=>'微信支付',
		2=>'线下支付',
		3=>'库存支付',
	);
	
	//退款类型
	public $refundType=array(
			1=>'退款',
			2=>'退货',
	);
	
	public $errorStatus=array(
		'SYSTEMERROR'=>'接口返回错误',
		'REFUNDNOTEXIST'=>'退款订单查询失败',
		'INVALID_TRANSACTIONID'=>'无效transaction_id',
		'PARAM_ERROR'=>'参数错误',
		'APPID_NOT_EXIST'=>'APPID不存在',
		'MCHID_NOT_EXIST'=>'MCHID不存在',
		'REQUIRE_POST_METHOD'=>'请使用post方法',
		'SIGNERROR'=>'签名错误',
		'XML_FORMAT_ERROR'=>'XML格式错误',
	);
	
	public function getTypeAttr($value)
	{
		$status = [1=>'商品订单',2=>'礼包订单'];
		return $status[$value];
	}
	
	public function getAuthStatusAttr($value)
	{
		$status = [1=>'未审核',2=>'审核通过',3=>'审核拒绝'];
		return $status[$value];
	}
	
	public function getRefundStatusAttr($value)
	{
		$status = [1=>'未退款',2=>'退款成功',3=>'退款失败'];
		return $status[$value];
	}
	
	public function getRefundPayTypeAttr($value)
	{
		$status = [1=>'微信支付',2=>'线下支付',3=>'库存支付'];
		return $status[$value];
	}
	
	public function getRefundTypeAttr($value)
	{
		$status = [1=>'退款',2=>'退货'];
		return $status[$value];
	}
	
	//定义和代理商表的关联
	public function agents()
	{
		return $this->hasOne('Agents','agent_id','agent_id');
	}
	
	//获取订单的退款状态
	public function getAuthStatusByOrderNumber($order_number)
	{
		$status=$this->where("order_number='".$order_number."'")->value('refund_status');
	 
		return $status;
	}
}
