<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrdersTable extends Migrator
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
        $this->table('agent_orders')
        ->addColumn(Column::string('order_number')->setUnique()->setComment('订单编号'))

        ->addColumn(Column::integer('pid')->setComment('产品ID'))
        ->addColumn(Column::string('pname')->setComment('产品名称'))
        ->addColumn(Column::decimal('pprice',10,2)->setDefault(0)->setComment('商品单价'))
        
        ->addColumn(Column::decimal('ptotal_price',10,2)->setDefault(0)->setComment('商品总价'))
        ->addColumn(Column::integer('pnumber')->setDefault(1)->setComment('产品数量'))
        
   		->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
	   
	    ->addColumn(Column::tinyInteger('paystyle')->setDefault(1)->setComment('支付方式1是线下支付2是微信'))
	    ->addColumn(Column::integer('pay_time')->setComment('订单支付时间'))
	    ->addColumn(Column::integer('pay_id')->setDefault(0)->setComment('支付信息表ID'))
	    
	    ->addColumn(Column::integer('refund_time')->setComment('订单退款时间'))
	    ->addColumn(Column::integer('order_refund_id')->setDefault(0)->setComment('订单退款信息表ID'))
	    
	    ->addColumn(Column::tinyInteger('isvalid')->setDefault(1)->setComment('1是有效2是失效'))
	    ->addColumn(Column::integer('create_time')->setComment('订单创建时间'))
	    
	    ->addColumn(Column::decimal('order_amount',10,2)->setDefault(0)->setComment('订单金额'))
	    ->addColumn(Column::decimal('order_amount_pay',10,2)->setDefault(0)->setComment('订单支付金额'))
	    ->addColumn(Column::decimal('ptotal_price_pay',10,2)->setDefault(0)->setComment('商品支付总价'))
	    ->addColumn(Column::decimal('trans_expenses',10,2)->setDefault(0)->setComment('订单运费'))
	    
	    ->addColumn(Column::tinyInteger('order_status')->setDefault(1)->setComment('订单状态1待支付2待发货3已发货4交易完成 5已关闭 6订单删除7售后服务 '))
	    ->addColumn(Column::string('remark')->setComment('订单备注'))
	    ->addColumn(Column::string('agent_remark')->setComment('订单买家备注'))
	    ->addColumn(Column::integer('delivery_time')->setComment('订单发货时间'))
	    ->addColumn(Column::integer('delivery_agent_id')->setComment('订单发货代理商其中ID为1是公司发货'))
	    
	    ->addColumn(Column::integer('commplete_time')->setComment('订单完成时间'))
	    
	    ->addColumn(Column::integer('delivery_id')->setComment('发货信息表ID'))
	    ->addColumn(Column::string('reward_id')->setComment('订单奖励表ID'))
	    ->addColumn(Column::integer('consignee_address_id')->setComment('收货人地址表ID'))
	    
        ->create(); 
    }
     public function down()
    {
        $this->dropTable('agent_orders');
    }
}
