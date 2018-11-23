<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateShop extends Migrator
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
        $this->table('shop')
        ->addColumn(column::integer('a_id')->setComment('agent_id'))
        ->addColumn(column::string('shop_name')->setComment('店铺名称'))
        ->addColumn(column::string('background')->setDefault('/static/Images/picture_default.png')->setComment('店铺背景图'))
        ->addColumn(column::string('qrcode')->setComment('店铺二维码图'))
        ->addColumn(column::integer('fans')->setDefault(0)->setComment('粉丝数'))
        ->addColumn(column::integer('pv')->setDefault(0)->setComment('访客数'))
        ->addColumn(Column::decimal('sale',10,2)->setDefault(0)->setComment('销售金额'))
        ->addColumn(column::integer('orders')->setDefault(0)->setComment('订单数'))

        ->create();
    }

    public function down()
    {
        $this->dropTable('shop');
    }
}
