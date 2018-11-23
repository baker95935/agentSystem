<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateShopGoods extends Migrator
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
        $this->table('shop_goods')
        ->addColumn(column::integer('a_id')->setComment('agent_id'))
        ->addColumn(column::integer('p_id')->setComment('product_id'))
        ->addColumn(column::integer('type')->setComment('库内商品:1上架 2下架'))
        ->addColumn(column::integer('is_del')->setDefault(0)->setComment('是否删除:0未删 1删除'))
        ->addColumn(column::datetime('create_ctime')->setComment('添加时间'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('shop_goods');
    }
}