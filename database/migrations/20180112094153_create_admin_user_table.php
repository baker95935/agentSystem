<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAdminUserTable extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
	public function up()
	{
	  $this->table('admin_user',['engine'=>'MyISAM'])
	    ->addColumn(Column::string('username')->setComment('用户名'))
	    ->addColumn(Column::string('password')->setComment('密码'))
	    ->addColumn(Column::string('realname')->setComment('真实姓名'))
	    ->addColumn(Column::string('email')->setComment('邮箱'))
	    ->addColumn(Column::string('group')->setComment('model名称'))
	    ->addColumn(Column::tinyInteger('status')->setComment('状态'))
	    ->addColumn(Column::integer('count')->setComment('登录次数'))
	    ->addColumn(Column::integer('create_time')->setComment('创建时间'))
	    ->addColumn(Column::integer('update_time')->setComment('更新时间'))
	    ->create();
	}
	
	public function down()
	{
		$this->dropTable('admin_user');
	}
}
