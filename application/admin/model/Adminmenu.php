<?php

namespace app\admin\model;

use think\Model;

class Adminmenu extends Model
{
	protected $table = 'admin_menu';

	/**
	 * 读取菜单名称
	 */
	public function getMenuForMenuName($data){

		return $this->field('name')->where('module',$data)->find();

	}
}
