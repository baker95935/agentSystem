<?php

namespace app\index\model;

use think\Model;
use think\Db;
use think\Cache;

class Agents extends Model
{
	//定义缓存时间
	protected $cache_time=1800;

    protected $table = 'agents';

    //定义和微信信息表的关联
    public function wechat()
    {
        return $this->belongsTo('Weixinusers','agent_id','agent_id');
    }

    /**
     * 通过ID获取代理商基本表信息
     *
     * @param $aid int 代理商ID
     * @param $field string 要查询字段的字符串
     * @return obj
     */
    public function getAgentInfoByAid($aid,$field='')
    {
    	$field = empty($field) ? 'nickname,name,wechat,phone,create_ctime,end_etime,generation,role,inviter,family,sex,id_card,province,city,area,address,stock_money,status,is_del,head_img,is_use' : $field;
    	return $info = $this->field($field)->where(['agent_id'=>$aid])->find();
    }

    /**
     * 自定义条件查询某代理商信息并返回数组
     * @param  array  $condition  查询条件
     * @return array              一维数组或空数组
     */
    public function getInfoByCondition($condition = ['is_del'=>0])
    {
        $return = [];
        $result = $this->where($condition)->find();
        if($result)
        {
            $return = $result;
        }
        return $return;
    }

    /**
     *获取代理商正常下级的ID
     *	@param $aid  代理商ID
     *  @param $role 代理商等级
     *  return array
     */
    public function getAllSonAgentId($aid,$role)
    {
    	$res=array();


    	$memKey='getAllSonAgentId_'.$aid.'_'.$role;
    	//$res=Cache::get($memKey);

    	if(empty($res)) {
	    	//先获直属并且等级大于的
	    	$data=array();
	    	$data[]=['exp','FIND_IN_SET('.$aid.' ,family)'];
	    	$userList=$this->where($data)->field('agent_id,role,family')->select();

    		foreach($userList as $k=>$v) {

	    		if($role>$v['role']) {

	    			//family进行判断，找到离他最近的
	    			$familyAry=explode(',',$v['family']);
	    			$familyAry=array_reverse($familyAry);

					$maxK=0;	//设置role变量的值，获取最高的，然后
	    			$highrole=0;
					$maxK=array_search($aid,$familyAry);//如果找到了当前用户，那么设置最大循环次数

	    			foreach($familyAry as $kk=>$vv) {

	    				//获取对应ID的等级
	    				//校验在获取这个信息之前 有没有比role等级大的，如果有，说明被劫持跳转
	    				$tmprole=$this->where('agent_id='.$vv)->value('role');

	    				$kk==0 && $highrole=$tmprole;

	    				//如果不是第一次，并且角色比最高等级高，并且小于最大循环次数
		    			if($kk>0 && $tmprole>$highrole && $kk<=$maxK-1) {

		    				$highrole=$tmprole;
		    			}

	    			}

	    			//获取数据,如果只有一个上级，那么可以相当
	 				if($maxK==0) {
	 					if($role>=$highrole) {
	 						$res[$k]=$v['agent_id'];
	 					}
	 				} else {
		    			if($role>$highrole) {
		    				$res[$k]=$v['agent_id'];
		    			}
	 				}

	    		}
	    	}

	    	!empty($res) && Cache::set($memKey,$res,$this->cache_time);
    	}

    	return $res;
    }

    /**
     * 按自定义条件获取代理商ID
     *
     * @param mixed $where['role'=>['lt',$role],...,'is_del'=>0]|'is_del=0 AND ....'
     * @return array[100001,100002,...,n]
     */
    public function getAgentAllSonsId($where)
    {
        $return = [];
        $list = $this->field('agent_id')->where($where)->select();
        if($list)
        {
            foreach ($list as $key => $val)
            {
                $return[] = $val['agent_id'];
            }
        }
        return $return;
    }

    /**
     * 获取代理商团队成员ID
     * @param  integer $aid    代理商ID
     * @param  integer $role   代理商身份等级
     * @param  integer $type   类型:1全部用户包含已删除(会员|代理商) 2全部用户不包含已删除 3仅代理商包含已删除 4仅代理商不包含已删除 5仅会员并包含已删除 6仅会员不包含已删除
     * @return array          [n1,n2,...,n]
     */
    public function getSons($aid,$role,$type = 1)
    {
        $upper = $return = $upper_sons = [];
        $list = $this->field('agent_id,role,is_del')->where('find_in_set("'.$aid.'",family)')->select();// 在族谱中查询并获取所有包含该ID的代理商ID

        if($list)
        {
            foreach ($list as $key => $val)
            {
                if ($role <= $val['role'])
                {// 代理商等级小于族谱中下线代理商的等级
                    $upper[] = $val['agent_id'];
                }
                switch ($type)
                {
                    case 2:
                        $val['is_del'] == 0 && $return[] = $val['agent_id'];
                        break;
                    case 3:
                        $val['role'] > 0 && $return[] = $val['agent_id'];
                        break;
                    case 4:
                        ($val['role'] > 0 && $val['is_del'] == 0) && $return[] = $val['agent_id'];
                        break;
                    case 5:
                        $val['role'] == 0 && $return[] = $val['agent_id'];
                        break;
                    case 6:
                        ($val['role'] == 0 && $val['is_del'] == 0) && $return[] = $val['agent_id'];
                        break;
                    case 1:
                    default:
                        $return[] = $val['agent_id'];
                        break;
                }
            }

            if(!empty($upper))
            {
                $where = '( ';// 查询截断ID的子ID的条件
                for ($i=0; $i < count($upper); $i++)
                {
                    $where .= 'find_in_set("'.$upper[$i].'",family) OR ';
                }
                $where = rtrim($where,'OR ').' ) AND '.'find_in_set("'.$aid.'",family)';
                $upper_sons = $this->getAgentAllSonsId($where);
            }
            $return = array_diff($return,$upper_sons,$upper);// 去除截断后的代理商ID
        }
        return $return;
    }

    //意向代理 获取同等级的直属sonID
    public function getSonIdByRole($agent_id,$role)
    {
    	$res=array();
    	$list=$this->where('inviter='.$agent_id.' and role='.$role)->field('agent_id')->select();
    	foreach($list as $k=>$v) {
    		$res[]=$v['agent_id'];
    	}
    	return $res;
    }

    //代理的收益进行增加或者减少 1加2减
    public function operateAgentProfit($agent_id,$money,$type=1)
    {
    	$res=0;
    	//对数据进行校验，减得时候
    	if($type==2) {
    		$agentInfo=$this->find($agent_id);
    		if($agentInfo['profit']<$money) {
    			return $res;
    		}
    	}

    	$type==1 && $res=$this->where('agent_id',$agent_id)->setInc('profit',$money);
    	$type==2 && $res=$this->where('agent_id',$agent_id)->setDec('profit',$money);
    	return $res;
    }

    //代理商提现申请
    public function agentApplyWithdrawals($data=array())
    {
    	$res=0;

    	//开启事务
    	Db::startTrans();//开启事务

    	try{
    		//插入到记录
    		$m_withdrawals_log = model('admin/WithdrawalsLog');
    		$result = $m_withdrawals_log->insert($data);

    		if($result) {
    			$res=$this->where('agent_id',$data['a_id'])->setDec('profit',$data['money']);
    		}
    		$res=1;
    		//执行事务
			Db::commit();
		} catch (\Exception $e) {

		    //回滚事务
		    Db::rollback();
		}

		return $res;
    }

    //获取代理的直接下级
    public function getAgentDirectLowerList($agent_id)
    {
    	$res=array();

    	$list=$this->where("inviter=".$agent_id)->order('agent_id desc')->select();
    	if($list)
        {
            foreach ($list as $key => $val)
            {
                $res[] = $val['agent_id'];
            }
        }
        return $res;
    }
}