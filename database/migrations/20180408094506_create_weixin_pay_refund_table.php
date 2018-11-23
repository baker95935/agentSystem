<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWeixinPayRefundTable extends Migrator
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
        $this->table('weixin_pay_refund')
        ->addColumn(Column::string('out_trade_no')->setComment('商户订单号'))
        ->addColumn(column::string('sign')->setComment('签名'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(Column::string('out_refund_no')->setComment('商户退款单号'))
        
    
        ->addColumn(Column::string('transaction_id')->setComment('微信订单号'))
        ->addColumn(Column::string('refund_id')->setComment('微信退款单号'))
        
        ->addColumn(column::integer('total_fee')->setComment('订单总金额，单位为分'))
        ->addColumn(column::integer('refund_fee')->setComment('退款金额，单位为分'))
        
        ->addColumn(column::integer('update_time')->setComment('更新时间'))
        ->addColumn(column::tinyInteger('status')->setComment('退款状态1未退款2退款成功3退款失败'))
        
        ->addColumn(Column::string('return_code')->setComment('返回状态码'))
        ->addColumn(Column::string('return_msg')->setComment('返回信息'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('weixin_pay_refund');
    }
}
