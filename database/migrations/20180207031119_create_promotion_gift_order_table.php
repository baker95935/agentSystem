<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePromotionGiftOrderTable extends Migrator
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
	  $this->table('promotion_gift_order')
	  	->addColumn(Column::string('order_number')->setComment('订单编号'))
	  	->addColumn(column::tinyInteger('status')->setComment('礼包状态1待确认2待发货3已发货4待完成5已关闭6订单删除7售后服务'))
	  	->addColumn(Column::integer('create_time')->setDefault(0)->setComment('下单日期'))
	  	->addColumn(Column::integer('delivery_time')->setDefault(0)->setComment('发货日期'))
	  	->addColumn(Column::integer('confirm_time')->setDefault(0)->setComment('收货日期'))
	  	->addColumn(Column::integer('complete_time')->setDefault(0)->setComment('完成日期'))
	  	
	  	->addColumn(Column::integer('refund_time')->setDefault(0)->setComment('退款时间'))
	  	->addColumn(Column::integer('order_refund_id')->setDefault(0)->setComment('退款申请表ID'))
	  	
	  	->addColumn(Column::integer('pay_time')->setDefault(0)->setComment('支付日期'))
	  	->addColumn(Column::integer('pay_id')->setDefault(0)->setComment('支付信息表ID'))
	  	->addColumn(column::tinyInteger('paystyle')->setDefault(1)->setComment('礼包状态1是下线支付2微信支付'))
	  	
	  	->addColumn(Column::integer('gift_id')->setDefault(0)->setComment('礼包ID'))
	  	
	  	->addColumn(Column::integer('agent_id')->setDefault(0)->setComment('代理ID'))
	  	
	  	->addColumn(Column::string('consignee_name')->setComment('收货人姓名'))
	  	->addColumn(Column::string('consignee_phone')->setComment('收货人手机'))
	  	->addColumn(Column::integer('consignee_province')->setDefault(0)->setComment('收货人省份'))
	  	->addColumn(Column::integer('consignee_city')->setDefault(0)->setComment('收货人城市'))
	  	->addColumn(Column::integer('consignee_area')->setDefault(0)->setComment('收货人地区'))
	  	->addColumn(Column::string('consignee_address')->setComment('收货人地址'))
	  	
	  	->addColumn(Column::string('express_name')->setComment('物流名称'))
	  	->addColumn(Column::string('express_number')->setComment('物流编号'))
	  	->addColumn(Column::string('express_remark')->setComment('发货备注'))
	  	
	  	->addColumn(Column::string('order_remark')->setComment('订单备注'))
	  	->addColumn(Column::integer('pnumber')->setDefault(0)->setComment('订单商品数量'))
	  	->addColumn(Column::decimal('order_price',10,2)->setDefault(0)->setComment('订单商品支付总价'))
 
	  	->addColumn(Column::string('express_code')->setComment('物流公司代码'))
	  	
	    ->create();
	}
	
	public function down()
	{
		$this->dropTable('promotion_gift_order');
	}
}
