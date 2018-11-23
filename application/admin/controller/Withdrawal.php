<?php
namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;

class Withdrawal extends Common
{

	/**
	 * 提现管理(done)
	 */
	public function index()
	{
		$m_withdrawals = model('WithdrawalsLog');
		$m_agent_level = model('Agentlevel');

		$levelList=$m_agent_level->gerRoleList();//等级数据列表
		$this->assign('levelList',$levelList);

		$audit_lang = $m_withdrawals->audit;
		$type_lang  = $m_withdrawals->type;
        $received = request();
		$search['a_id']  = $received->param('a_id');
		$search['name']  = $received->param('name');
		$search['phone'] = $received->param('phone');
		$search['role']  = $received->param('role');
		$search['audit'] = $received->param('audit');
		$search['type']  = $received->param('type');
		$search['apply_stime'] = $received->param('apply_stime');
		$search['apply_etime'] = $received->param('apply_etime');
		$search['audit_stime'] = $received->param('audit_stime');
		$search['audit_etime'] = $received->param('audit_etime');
        $where = ['a.is_del'=>0];// 查询条件
		if(!empty($search['a_id']))
		{
			$where['w.a_id'] = $search['a_id'];
		}
		if(!empty($search['name']))
		{
			$where['a.name'] = ['like','%'.$search['name'].'%'];
		}
		if(!empty($search['phone']))
		{
			$where['a.phone'] = $search['phone'];
		}
		if(in_array($search['role'], [0,1,2,3,4,5,6]) && isset($search['role']))
		{
			$where['a.role'] = $search['role'];
		}else{
			$search['role'] = -1;// 默认全部
		}
		if(!empty($search['audit']))
		{
			$where['w.audit'] = $search['audit'];
		}
		if(!empty($search['type']))
		{
			$where['w.type'] = $search['type'];
		}
		if(!empty($search['apply_stime']) && !empty($search['apply_etime']))
		{
			$where['w.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
		}else if(empty($search['apply_stime']) && !empty($search['apply_etime'])){
			$where['w.create_ctime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
			$search['apply_stime'] = date('Y-m-d');
		}else if(!empty($search['apply_stime']) && empty($search['apply_etime'])){
			$where['w.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',time()+86400)]];
			$search['apply_etime'] = date('Y-m-d');
		}
		if(!empty($search['audit_stime']) && !empty($search['audit_etime']))
		{
			$where['w.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
		}else if(empty($search['audit_stime']) && !empty($search['audit_etime'])){
			$where['w.audit_atime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
			$search['audit_stime'] = date('Y-m-d');
		}else if(!empty($search['audit_stime']) && empty($search['audit_etime'])){
			$where['w.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',time()+86400)]];
			$search['audit_etime'] = date('Y-m-d');
		}
		$list = $m_withdrawals->alias('w')->field('w.change_before,w.id,w.a_id,w.audit,w.money,w.type,w.create_ctime,w.audit_atime,w.remark,w.result,w.account,a.nickname,a.name,a.phone,a.wechat,a.generation,a.role,a.profit')->join('agents a','w.a_id=a.agent_id')->where($where)->order('w.id desc')->paginate(config('paginate.list_rows'));

		$this->assign('list',$list);
		$this->assign('audit_lang',$audit_lang);
		$this->assign('type_lang',$type_lang);
		$this->assign('search',$search);
		return $this->fetch();
	}

	/**
	 * 提现记录(done)
	 */
	public function history()
	{
		$m_withdrawals = model('WithdrawalsLog');
		$m_agent_level = model('Agentlevel');

		$audit_lang = $m_withdrawals->auditHistory;

		$type_lang  = $m_withdrawals->type;

		$levelList=$m_agent_level->gerRoleList();//等级数据列表
		$this->assign('levelList',$levelList);

        $received = request();
		$search['a_id']  = $received->param('a_id');
		$search['name']  = $received->param('name');
		$search['phone'] = $received->param('phone');
		$search['role']  = $received->param('role');
		$search['audit'] = $received->param('audit');
		$search['type']  = $received->param('type');
		$search['apply_stime'] = $received->param('apply_stime');
		$search['apply_etime'] = $received->param('apply_etime');
		$search['audit_stime'] = $received->param('audit_stime');
		$search['audit_etime'] = $received->param('audit_etime');
        $where = ['a.is_del'=>0];// 查询条件
		if(!empty($search['a_id']))
		{
			$where['w.a_id'] = $search['a_id'];
		}
		if(!empty($search['name']))
		{
			$where['a.name'] = ['like','%'.$search['name'].'%'];
		}
		if(!empty($search['phone']))
		{
			$where['a.phone'] = $search['phone'];
		}
		if(in_array($search['role'], [0,1,2,3,4,5,6]) && isset($search['role']))
		{
			$where['a.role'] = $search['role'];
		}else{
			$search['role'] = -1;// 默认全部
		}
		if(!empty($search['audit']))
		{
			$where['w.audit'] = $search['audit'];
		}
		if(!empty($search['type']))
		{
			$where['w.type'] = $search['type'];
		}
		if(!empty($search['apply_stime']) && !empty($search['apply_etime']))
		{
			$where['w.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
		}else if(empty($search['apply_stime']) && !empty($search['apply_etime'])){
			$where['w.create_ctime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
			$search['apply_stime'] = date('Y-m-d');
		}else if(!empty($search['apply_stime']) && empty($search['apply_etime'])){
			$where['w.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',time()+86400)]];
			$search['apply_etime'] = date('Y-m-d');
		}
		if(!empty($search['audit_stime']) && !empty($search['audit_etime']))
		{
			$where['w.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
		}else if(empty($search['audit_stime']) && !empty($search['audit_etime'])){
			$where['w.audit_atime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
			$search['audit_stime'] = date('Y-m-d');
		}else if(!empty($search['audit_stime']) && empty($search['audit_etime'])){
			$where['w.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',time()+86400)]];
			$search['audit_etime'] = date('Y-m-d');
		}
		$list = $m_withdrawals->alias('w')->field('w.id,w.a_id,w.audit,w.money,w.type,w.create_ctime,w.audit_atime,w.remark,w.result,a.nickname,a.name,a.phone,a.wechat,a.generation,a.role,a.profit')->join('agents a','w.a_id=a.agent_id')->where(['w.audit'=>['in','2,3']])->where($where)->order('w.id asc')->paginate(10);

		$this->assign('list',$list);

		$this->assign('audit_lang',$audit_lang);
		$this->assign('type_lang',$type_lang);
		$this->assign('search',$search);
		return $this->fetch();
	}

	/**
	 * 通过/驳回(done)
	 */
	public function audit($id,$status)
	{
		$manager = session('username');
		if(empty($id) || empty($status))
		{
			return ['error'=>['msg'=>'操作失败']];
		}
		$now = date('Y-m-d H:i:s');
		switch ($status)
		{
			case 'accept':
				$info = model('WithdrawalsLog')->alias('w')->field('w.a_id,w.audit,w.type,w.money,a.profit')->join('agents a','w.a_id=a.agent_id','LEFT')->where(['w.id'=>$id])->find();// 申请记录
				if(1 != $info->getData('audit'))
				{
					return ['error'=>['msg'=>'该申请不能修改']];
				}else{
					if($info->getData('type') == 3)
					{
						$openId = model('index/Weixinusers')->getUserOpenid($info['a_id']);// 获取该用户openid
						if(!$openId)
						{
							return ['error'=>''];
						}
						$order_number = date('YmdHis').$info['a_id'].get_mt_rand(6);
						$withdraw_data = [
			                'money'          => $info['money'],
			                'businessman_sn' => $order_number,
			                'weixin_openid'  => $openId,
			                'txnickname'     => '',// 用户姓名(非强制检查可为空)
			            ];
						$wx_return = $this->withdrawToWechat($withdraw_data);// 微信提现
						if(isset($wx_return['msg']) && $wx_return['msg'] == '操作成功')
			            {
			             	model('WithdrawalsLog')->save(['audit_atime'=>$now,'auditer'=>$manager,'audit'=>2,'remark'=>'系统自动打款'.$info->money.'元 ['.$order_number.']'],['id'=>$id]);
			                return ['msg'=>'提现成功'];
			            }else{
			                return ['error'=>['msg'=>'操作失败']];
			            }
					}else{
						$result = model('WithdrawalsLog')->save(['audit_atime'=>$now,'auditer'=>$manager,'audit'=>2,'remark'=>'手动线下打款'.$info->money.'元'],['id'=>$id]);
						if ($result)
						{
							return ['msg'=>'操作成功'];
						} else {
							return ['error'=>['msg'=>'操作失败']];
						}
					}
				}
				break;
			case 'refuse':
				$result = model('WithdrawalsLog')->save(['audit_atime'=>$now,'auditer'=>$manager,'audit'=>3],['id'=>$id]);
				$info = model('WithdrawalsLog')->alias('w')->field('w.a_id,w.audit,w.money,a.profit')->join('agents a','w.a_id=a.agent_id','LEFT')->where(['w.id'=>$id])->find();
				model('Agents')->save(['profit'=>($info->profit + $info->money)],['agent_id'=>$info->a_id]);// 加法
				if ($result)
				{
					return ['msg'=>'操作成功'];
				} else {
					return ['error'=>['msg'=>'操作失败']];
				}
				break;
			default:
				return ['error'=>['msg'=>'操作失败']];
				break;
		}
	}

	/**
	 * 导出(done)
	 */
	public function export()
	{
		$received = request();
		$type = $received->param('t');// 导出类型1:所有状态2审核过的
		$search['a_id']  = $received->param('a_id');
		$search['name']  = $received->param('name');
		$search['phone'] = $received->param('phone');
		$search['role']  = $received->param('role');
		$search['audit'] = $received->param('audit');
		$search['type']  = $received->param('type');
		$search['apply_stime'] = $received->param('apply_stime');
		$search['apply_etime'] = $received->param('apply_etime');
		$search['audit_stime'] = $received->param('audit_stime');
		$search['audit_etime'] = $received->param('audit_etime');
		// 导出设置
		if ($type == 1)
		{
			$data_field = array('代理商ID','姓名','微信号','手机号','角色','账户类型','申请提现金额','可提现金额','审核状态','申请日期','审核日期','备注');
			$filename = '提现管理';
		} else {
			$data_field = array('代理商ID','姓名','微信号','手机号','角色','审核状态','申请提现金额','账户类型','申请日期','审核日期','备注');
			$filename = '提现记录';
		}
		$where = $agent_where = array();// 查询条件
		if(!empty($search['a_id']))
		{
			$where['WithdrawalsLog.a_id'] = $search['a_id'];
		}
		if(!empty($search['name']))
		{
			$agent_where['agents.name'] = ['like','%'.$search['name'].'%'];
		}
		if(!empty($search['phone']))
		{
			$agent_where['agents.phone'] = $search['phone'];
		}
		if(in_array($search['role'], [0,1,2,3,4,5,6]) && isset($search['role']))
		{
			$agent_where['agents.role'] = $search['role'];
		}
		if(!empty($search['audit']))
		{
			$where['WithdrawalsLog.audit'] = $search['audit'];
		}
		if(!empty($search['type']))
		{
			$where['WithdrawalsLog.type'] = $search['type'];
		}
		if(!empty($search['apply_stime']) && !empty($search['apply_etime']))
		{
			$where['WithdrawalsLog.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
		}else if(empty($search['apply_stime']) && !empty($search['apply_etime'])){
			$where['WithdrawalsLog.create_ctime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['apply_etime'].' + 1 day'))]];
		}else if(!empty($search['apply_stime']) && empty($search['apply_etime'])){
			$where['WithdrawalsLog.create_ctime'] = ['between',[$search['apply_stime'],date('Y-m-d',time()+86400)]];
		}
		if(!empty($search['audit_stime']) && !empty($search['audit_etime']))
		{
			$where['WithdrawalsLog.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
		}else if(empty($search['audit_stime']) && !empty($search['audit_etime'])){
			$where['WithdrawalsLog.audit_atime'] = ['between',[date('Y-m-d'),date('Y-m-d',strtotime($search['audit_etime'].' + 1 day'))]];
		}else if(!empty($search['audit_stime']) && empty($search['audit_etime'])){
			$where['WithdrawalsLog.audit_atime'] = ['between',[$search['audit_stime'],date('Y-m-d',time()+86400)]];
		}
		$data = array();// 输出数据
		if($type == 1)
		{
			$list = model('WithdrawalsLog')->hasWhere('agents',$agent_where)->where($where)->select();// 查询所有数据
			foreach($list as $k=>$v)
			{
				$data[$k]['a_id']         = $v['a_id'];
				$data[$k]['name']         = $v->agents->name;
				$data[$k]['wechat']       = $v->agents->wechat;
				$data[$k]['phone']        = $v->agents->phone;
				$data[$k]['rolename']     = get_reward_levelname($v->agents->role);
				$data[$k]['type']         = $v['type'];
				$data[$k]['money']        = $v['money'];
				$data[$k]['profit']       = $v->agents->profit;
				$data[$k]['audit']        = $v['audit'];
				$data[$k]['create_ctime'] = $v['create_ctime'];
				$data[$k]['audit_atime']  = $v['audit_atime'];
				$data[$k]['remark']       = $v['remark'];
			}
		}else{// ,'申请日期','审核日期','备注'
			$list = model('WithdrawalsLog')->hasWhere('agents',$agent_where)->where(['WithdrawalsLog.audit'=>['in','2,3']])->where($where)->select();// 查询所有数据
			foreach($list as $k=>$v)
			{
				$data[$k]['a_id']         = $v['a_id'];
				$data[$k]['name']         = $v->agents->name;
				$data[$k]['wechat']       = $v->agents->wechat;
				$data[$k]['phone']        = $v->agents->phone;
				$data[$k]['rolename']     = get_reward_levelname($v->agents->role);
				$data[$k]['audit']        = $v['audit'];
				$data[$k]['money']        = $v['money'];
				$data[$k]['type']         = $v['type'];
				$data[$k]['create_ctime'] = $v['create_ctime'];
				$data[$k]['audit_atime']  = $v['audit_atime'];
				$data[$k]['remark']       = $v['remark'];
			}
		}
		$this->exportexcel($data,$data_field,$filename);
	}

	// 微信提现功能
    private function withdrawToWechat($record)
    {
    	ini_set('date.timezone','Asia/Shanghai');
    	error_reporting(E_ERROR);
    	require_once "../vendor/weixin/WxPay.Api.php";
    	require_once '../vendor/weixin/log.php';
        //初始化日志
        $logHandler= new \CLogFileHandler("../logs/withdrawToWechat".date('Y-m-d').'.log');
		$log = \Log::Init($logHandler, 15);
        /**
         * 微信提现
         */
        $branch   = number_format($record["money"],"2",".","");// 提现金额
        $postData = array(
			"mch_appid"        => config('wechat.appid'),		//绑定支付的APPID
			"mchid"            => \WxPayConfig::MCHID,			//商户号
			"nonce_str"        => "rand".rand(100000, 999999),	//随机数
			"partner_trade_no" => $record["businessman_sn"],	//商户订单号
			"openid"           => $record["weixin_openid"],		//用户唯一标识
			"check_name"       => "NO_CHECK",					//NO_CHECK：不校验 FORCE_CHECK：强校验真实姓名
			"re_user_name"     => $record["txnickname"],			//用户姓名
			"amount"           => $branch*100,					//分为单位，必须大于100
			"desc"             => "您的提现申请受理成功，请在微信零钱进行查看", //描述
			"spbill_create_ip" => $_SERVER["REMOTE_ADDR"],		//请求ip
        );
        /**
         * 生成签名
         */
        ksort($postData);
        $buff = "";
        foreach ($postData as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        $string = $string . "&key=".\WxPayConfig::KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $sign = strtoupper($string);
        $postData["sign"] = $sign;
        /**
         * 组装xml数据
         */
        $xml = "<xml>";
        foreach ($postData as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        /**
         * 发送post请求
         */
        $url="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		//如果有配置代理这里就设置代理
		if(\WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
            && \WxPayConfig::CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, \WxPayConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, \WxPayConfig::CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, \WxPayConfig::SSLCERT_PATH);
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, \WxPayConfig::SSLKEY_PATH);
		//post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$dataRe = curl_exec($ch);
        //返回结果
        if($dataRe){
            curl_close($ch);
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $dataArr = json_decode(json_encode(simplexml_load_string($dataRe, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            if($dataArr["return_code"] == "SUCCESS" && $dataArr["result_code"] == "SUCCESS")
            {
                return ['msg'=>'操作成功'];
            }elseif($dataArr["err_code"] == "SYSTEMERROR"){
                \Log::DEBUG("error:" . $dataRe."\r\n");
                return ['error'=>'微信系统繁忙，请稍后再试。'];
            }else{
                \Log::DEBUG("error:" . $dataRe."\r\n");
                // throw new \Exception('微信提现失败:'.$dataArr["err_code_des"]);
                return ['error'=>'微信提现失败:'.$dataArr["err_code_des"]];
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            \Log::DEBUG("error:" . $error."\r\n");
            // throw new \Exception("curl出错，错误码:$error");
            return ['error'=>"curl出错，错误码:$error"];
        }
    }
}