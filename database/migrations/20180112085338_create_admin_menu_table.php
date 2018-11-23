<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAdminMenuTable extends Migrator
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
	  $this->table('admin_menu',['engine'=>'MyISAM'])
	    ->addColumn(Column::string('name')->setComment('名称'))
	    ->addColumn(Column::integer('parent_id')->setComment('父ID'))
	    ->addColumn(Column::string('group')->setComment('group名称'))
	    ->addColumn(Column::string('module')->setComment('model名称'))
	    ->addColumn(Column::string('action')->setComment('action名称'))
	    ->addColumn(Column::string('remark')->setComment('备注'))
	    ->addColumn(Column::tinyInteger('status')->setComment('状态'))
	    ->addColumn(Column::integer('create_time')->setComment('添加时间'))
	    ->addColumn(Column::integer('order')->setDefault(1)->setComment('排序'))
	    ->create();
	}
	
	public function down()
	{
		$this->dropTable('admin_menu');
	}
}
