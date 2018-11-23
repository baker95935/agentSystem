<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderRefundTable extends Migrator
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
        $this->table('agent_order_refund')
        ->addColumn(Column::string('order_number')->setComment('商户订单号'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::decimal('order_amount_pay',10,2)->setComment('订单总金额'))
        ->addColumn(column::decimal('refund_fee',10,2)->setComment('退款总金额'))
        ->addColumn(column::integer('agent_id')->setComment('agent_id'))
        
        ->addColumn(column::tinyInteger('type')->setComment('订单类型1商品订单2礼包订单'))
        ->addColumn(column::tinyInteger('refund_type')->setComment('退款类型1退款2退货'))
        
        ->addColumn(column::integer('refund_time')->setComment('退款更新时间'))
        ->addColumn(column::tinyInteger('refund_status')->setDefault(1)->setComment('退款状态1未退款2退款成功3退款失败'))
        
        ->addColumn(column::integer('refund_pay_id')->setComment('退款支付ID'))
        ->addColumn(column::tinyInteger('refund_pay_type')->setComment('退款支付类型1是微信支付2线下支付3库存支付'))
        
        ->addColumn(column::integer('auth_time')->setComment('审核时间'))
        ->addColumn(column::tinyInteger('auth_status')->setDefault(1)->setComment('审核状态1未审核2审核通过3审核拒绝'))
        ->addColumn(column::string('author')->setComment('审核人'))
        ->addColumn(column::string('reason')->setComment('理由'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_order_refund');
    }
}
