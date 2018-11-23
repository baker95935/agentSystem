<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTableAgentOrdersChangePrice extends Migrator
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
        $this->table('agent_orders_change_price')

        ->addColumn(Column::integer('create_time')->setComment('创建时间'))
        ->addColumn(Column::string('operator')->setComment('操作人'))
        
        ->addColumn(Column::string('new_order_number')->setComment('新订单号'))
        ->addColumn(Column::decimal('new_price',10,2)->setDefault(0)->setComment('订单现在价格'))
        
        ->addColumn(Column::string('original_order_number')->setComment('原订单编号'))
        ->addColumn(Column::decimal('original_price',10,2)->setDefault(0)->setComment('订单原始价格'))
        
        ->addColumn(Column::string('agent_order_ids')->setComment('原订单表编号串唯一标识'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_orders_change_price');
    }
}
