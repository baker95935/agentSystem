<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentStockTransferTable extends Migrator
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
        $this->table('agent_stock_transfer')
        ->addColumn(column::integer('operater_agent_id')->setComment('转账人操作人ID'))
        ->addColumn(column::integer('agent_id')->setComment('转账目标人ID'))
        ->addColumn(Column::decimal('money',10,2)->setDefault(0)->setComment('转账额度'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::tinyInteger('status')->setDefault(1)->setComment('1是成功2是失败'))

        ->create();
    }

    public function down()
    {
        $this->dropTable('agent_stock_transfer');
    }
}
