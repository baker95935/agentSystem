<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWeixinPayTable extends Migrator
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
        $this->table('weixin_pay')
        ->addColumn(Column::string('out_trade_no')->setComment('商户订单号'))
        ->addColumn(column::string('sign')->setComment('签名'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::string('body')->setComment('商品描述'))
        ->addColumn(column::integer('total_fee')->setComment('订单总金额，单位为分'))
        ->addColumn(Column::string('notify_url',1000)->setComment('通知地址'))
        ->addColumn(column::string('trade_type')->setComment('交易类型'))
        ->addColumn(column::string('openid')->setComment('用户标识'))
        ->addColumn(column::integer('userid')->setComment('userid'))
        ->addColumn(column::integer('update_time')->setComment('更新时间'))
        ->addColumn(column::tinyInteger('status')->setComment('支付状态1未支付2支付成功'))
        ->addColumn(column::string('transaction_id')->setComment('微信支付订单号'))
        ->addColumn(column::tinyInteger('pay_type')->setComment('支付类型1商品支付2礼包支付3库存充值'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('weixin_pay');
    }
}
