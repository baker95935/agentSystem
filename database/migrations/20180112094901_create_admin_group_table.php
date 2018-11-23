<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAdminGroupTable extends Migrator
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
	  $this->table('admin_group',['engine'=>'MyISAM'])
	    ->addColumn(Column::string('name')->setComment('名称'))
	    ->addColumn(Column::string('rights')->setComment('权限'))
	    ->addColumn(Column::tinyInteger('super')->setComment('是否超级管理员1是2否'))
	    ->addColumn(Column::string('remark')->setComment('备注'))
	    ->addColumn(Column::integer('create_time')->setComment('添加时间'))
	    ->create();
	}
	
	public function down()
	{
		$this->dropTable('admin_group');
	}
}
