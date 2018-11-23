<?php

namespace app\admin\controller;

use think\Controller;
use think\Session;

class Common extends Controller
{
	public function _initialize()
	{
		//登录校验
		if(!Session::has('username') || !Session::has('password') || !Session::has('group'))
		{
			$this->redirect('/admin/Login/index/');
		}
	
	}
	
	//导出 $data内容二维数组  $title各个标题  $filename表名称
	public function exportexcel($data=array(),$title=array(),$filename)
	{
		$filename=urlencode($filename);
 		//header("Content-type:application/octet-stream");
		header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:attachment;filename=".$filename.".xls");
		header("Pragma: no-cache");

		header("Expires: 0");
		//导出xls 开始
		if (!empty($title)){
			foreach ($title as $k => $v) {
				$title[$k]=iconv("UTF-8", "GB2312",$v);
				//$title[$k]=iconv("BIG5", "GB2312",$v);
			}
			$title= implode("\t", $title);
			echo "$title\n";
		}
		if (!empty($data)){
			foreach($data as $key=>$val){
				foreach ($val as $ck => $cv) {
					//$data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
					$data[$key][$ck]= mb_convert_encoding($cv, "GBK", "UTF-8");
				}
				$data[$key]=implode("\t", $data[$key]);

			}
			echo implode("\n",$data);
		}
	}
}
