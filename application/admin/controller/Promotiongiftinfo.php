<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Promotiongiftinfo extends Controller
{
     
	
	//列表
    public function index()
    {
    	
    	$request = request();
    	$name=$request->param('name');
    	$type=$request->param('type');
    	 
    	$data=array();
    	!empty($name) && $data['name']=['like','%'.$name.'%'];
    	!empty($type) && $data['type']=$type;
    	
    	$gift=model('Promotiongift');
    	
    	$giftList=$gift->where($data)->order('type desc,id desc')->paginate(config('paginate.list_rows'));
    	$this->assign('giftList',$giftList);
    	
    	//开启的等级列表
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	$this->assign('listLevel',$listLevel);
    	
        return $this->fetch();
    }
    
    //创建礼包
    public function create()
    {
    	//开启的等级列表
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	$this->assign('listLevel',$listLevel);
    	
    	//获取默认铜牌等级会员的奖励设置
    	$min=$level->min('id');//获取最小等级也就是铜牌
    	$deep=$this->levelRewardList($min);
    	$this->assign('deep',$deep);
    	
    	//默认加载铜牌的数据
    	$data=array();
    	$data['type']=$min;
    	$this->assign('data',$data);
    	
    	//默认加载所有礼包商品供选择
    	$product=model('Productmanagement');
    	$productList=$product->getManagementlist(array('is_gift'=>1,'state'=>1));
    	$this->assign('productList',$productList);
    	
   		return $this->fetch(); 
    }
    
    public function edit()
    {
    	//初始化
    	$request = request();
    	$id=$request->param('id');
    	$gift = model('Promotiongift');
    	$reward=model('Promotiongiftreward');
    	
    	//开启的等级列表
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	$minLevel=$level->min('id');
    	$this->assign('minLevel',$minLevel);
    	$this->assign('listLevel',$listLevel);
    	
    	//默认加载所有礼包商品供选择
    	$product=model('Productmanagement');
    	$productList=$product->getManagementlist(array('is_gift'=>1,'state'=>1));
    	$this->assign('productList',$productList);
    	
    	$data=array();
    	if(!empty($id)){
    		//礼包数据获取
    		$data=$gift::get($id);
    		$deep=$this->levelRewardList($data['type']);
    		$this->assign('deep',$deep);
    	
    		//奖励数据获取
    		foreach($listLevel as $k=>&$v) {
    			 
    			$tmpValueAry=$tmpIdAry=array();//不能直接引用，使用中间量
    			for($i=1;$i<$deep+1;$i++) {
    				$rewardTmp=$reward->where(array('role'=>$v['id'],'hierarchy'=>$i,'gift_id'=>$data['id']))->order('id desc')->find();
    				if(!empty($rewardTmp['value'])){
    					$tmpValueAry[$i]=$rewardTmp['value'];
    					$tmpIdAry[$i]=$rewardTmp['id'];
    				}
    				$v['roleRatioValue']=$tmpValueAry;
    				$v['roleRatioId']=$tmpIdAry;
    			}
    		}
    	}
    	 
    	$this->assign('data',$data);
    	return $this->fetch();
    	
    }
    
    //等级对应的深度
    public function levelRewardList($id)
    {
    	$res=array();
    	
    	//查找开启的等级
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	
    	//不同等级的深度列表
    	$i=1;
    	foreach($listLevel as $k=>$v)
    	{
    		$res[$v['id']]=$i;
    		$i++;	
    	}
    	return $res[$id];
    }
    
    //对应等级的ID获取的可输入的深度框
    public function levelRewardListDiv($id)
    {
    	//查找开启的等级
    	$level=model('Agentlevel');
    	$listLevel=$level->where(['valid'=>1])->order('id','asc')->select();
    	$minLevel=$level->min('id');//获取最低的ID
    	 
    	//开始
    	$str='<table  class="table table-bordered table-hover  m10">';
    	//先循环标题，根据深度
    	$deep=$this->levelRewardList($id);
 
    	$str.="<tr><td width='10%' class='tableleft'>角色</td>";
    	for($i=0;$i<$deep;$i++)
    	{
    		$str.="<td>".($i+1)."度奖励系数</td>";
    	}
    	
    	//然后循环获取输入框
		foreach($listLevel as $k=>$v)
		{
			$str.="<tr><td width='10%' class='tableleft'>".$v['name']."</td>";
			for($i=0;$i<$deep;$i++)
			{	
				if($v['id']>=$id) {//当前等级和页面等级大于等于的时候显示所有
					$str.="<td  class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
				} else {
					
					//最低等级，循环次数，铜牌的,先用笨方法写了
					if($v['id']==$minLevel && $i==0) {
						$str.="<td colspan='6' class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
						break;
					} 
					
					//低等级，循环次数，银牌的
					if($v['id']==$minLevel+1 && $i==0) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+1 && $i==1) {
						$str.="<td colspan='5' class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
						break;
					}
					
					//低等级，循环次数，金牌的
					if($v['id']==$minLevel+2 && $i==0) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+2 && $i==1) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+2 && $i==2) {
						$str.="<td colspan='4' class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
						break;
					}
					
					//低等级，循环次数，钻石
					if($v['id']==$minLevel+3 && $i==0) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+3 && $i==1) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+3 && $i==2) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+3 && $i==3) {
						$str.="<td colspan='3' class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
						break;
					}
					
					//低等级，循环次数，皇冠
					if($v['id']==$minLevel+4 && $i==0) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+4 && $i==1) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+4 && $i==2) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+4 && $i==3) {
						$str.="<td class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
					}
					if($v['id']==$minLevel+4 && $i==4) {
						$str.="<td colspan='2' class='tl'><input style='width:140px;' class='input-small' type='text'   name='value_".$v['id']."_".$i."'></td>";
						break;
					}
					
					
				}
			}
		}
		$str.="</table>";
		
		return $str;
    }
 	
    
    /*
    //获取每个等级对应的输入框
    private function levelCycleDiv($str,$currentLevel,$minLevel,$var)
    {
    	for($i=0;$i<=$var;$i++)
    	{
    		if($currentLevel==$minLevel+$i) {
				$str.="<td><input class='input-small' type='text'   name='value_".$currentLevel."_".$i."'></td>";
				if($i==$var){
					break;
				}
			} 
    	}
    	
    	return $str;
    }*/
    
    //添加的数据保存
    public function save(Request $request)
    {
    	$data=$ndata=array();
    	
    	//获取等级数据的列表，需要批量获取数据
    	$level=model('Agentlevel');
    	$listLevel=$level->order('id','desc')->select();
    	
    	$giftReward=model('Promotiongiftreward');
    	$giftInfo=model('Promotiongift');
    	 
    	$ndata['name']=$request->param('name');
    	$ndata['pic']=$request->param('pic_url');
    	$ndata['type']=$request->param('type');
    	$ndata['price']=$request->param('price');
    	$ndata['number']=$request->param('number');
    	$ndata['product_id']=$request->param('product_id');
    	
    	$ndata['description']=$request->param('description');
    	$ndata['id']=$request->param('id');
    	
    	//如果类型变更，删除之前的记录
    	if($ndata['id']) {
    		$info=$giftInfo->find($ndata['id']);
    		if($info['type']!=$ndata['type']) {
    			$giftReward->where(['gift_id'=>$ndata['id']])->delete();
    		}
    	}
    	
    	//数据校验
    	$validate = validate('Promotiongiftinfo');
    		
    	if(!$validate->check($ndata)){
    		$this->error($validate->getError());
    	} else {
    			
    		$res=0;
    		if(empty($ndata['id'])){//添加
    			$ndata['create_time']=time();
    			$res=$giftInfo->save($ndata);
    			$giftId=$giftInfo->id;
    		} else {
    			$res=$giftInfo->save($ndata,array('id'=>$ndata['id']));//更新
    			$giftId=$ndata['id'];
    			$res=1;
    		}
    		
    	}
    	
    	//循环获取数据存储,获取循环的深度
    	$deep=$this->levelRewardList($ndata['type']);
    	$j=0;
    	foreach($listLevel as $k=>$v)
    	{
    		for($i=0;$i<$deep;$i++) {
    			
    			$data[$j]['gift_id']=$giftId;
    			$data[$j]['role']=$v['id'];
    			$data[$j]['hierarchy']=$i+1;
    			$data[$j]['value']=$request->param('value_'.$v['id'].'_'.$i);
    			if(empty($data[$j]['value'])){//金字塔中没有输入框的 也获取但是没有数据，所以咔嚓
    				unset($data[$j]); 
    			}
    			//获取存在的ID 就是编辑
    			$tmpId=$request->param('id_'.$v['id'].'_'.$i);
    			$tmpId>0 &&  $data[$j]['id']=$tmpId;
    			$j++;
    		}
    	}
 
 
    	//数据校验
    	$validate = validate('Promotiongiftreward');
    	 
    	foreach($data as $k=>$v) {
    		if(!$validate->check($v)){
    			$this->error($validate->getError());
    		}
    	}
    	
    	
    	
    	$result=$giftReward->saveAll($data);
    	
    	if($res) {
    		$this->success('操作成功', '/admin/Promotiongiftinfo/index/');
    	} else {
    		$this->success('操作失败，请重试', '/admin/Promotiongiftinfo/index/');
    	}
    	
    }
    
    
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
    	$gift=model('Promotiongift');
    	$reward=model('Promotiongiftreward');
    	$request = request();
    	 
    	if($request->method()=='GET') {
    		 
    		$id=$request->param('id');
    
    		$result=0;
    		$result=$gift->destroy($id);
    		$reward->where(['gift_id'=>$id])->delete();
    		 
    		if($result==0){
    			$this->success('操作失败，请重试');
    		} else {
    			$this->success('操作成功', '/admin/Promotiongiftinfo/index/');
    		}
    	}
    	 
    	$this->success('非法操作，请重试');
    }
}
