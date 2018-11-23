<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentStockChargeOrderTable extends Migrator
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
        $this->table('agent_stock_charge_order')
        ->addColumn(Column::string('order_number')->setComment('商户订单号'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::decimal('order_amount_pay',10,2)->setComment('订单总金额'))
        ->addColumn(column::integer('agent_id')->setComment('agent_id'))
        
        ->addColumn(column::tinyInteger('type')->setComment('充值类型1微信支付'))
        
        ->addColumn(column::integer('pay_time')->setComment('支付时间'))
        ->addColumn(column::tinyInteger('status')->setDefault(1)->setComment('支付状态1未支付2支付完成3支付失败'))
        ->addColumn(column::integer('pay_id')->setComment('支付表ID'))
        
        ->addColumn(column::integer('operator_agent_id')->setComment('操作人代理ID'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_stock_charge_order');
    }
}
