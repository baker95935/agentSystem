<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentLowerStockLogTable extends Migrator
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
        $this->table('agent_lower_stock_log')
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::integer('agent_id')->setComment('agent_id'))
        ->addColumn(column::integer('role')->setComment('role'))
        ->addColumn(column::integer('update_time')->setComment('更新时间'))
        ->addColumn(column::tinyInteger('status')->setDefault(1)->setComment('状态1未降级2已降级3降级失败'))
        ->addColumn(column::integer('down_time')->setComment('降级时间'))
        ->addColumn(column::integer('change_id')->setComment('降级信息关联ID'))
        ->addColumn(Column::string('remark')->setComment('备注'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_lower_stock_log');
    }
}
