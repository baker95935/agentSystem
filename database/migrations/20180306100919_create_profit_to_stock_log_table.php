<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateProfitToStockLogTable extends Migrator
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
    /* 收益转库存记录表 */
    public function up()
    {
        $this->table('profit_to_stock_log',['engine'=>'MyISAM'])
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::decimal('money',10,2)->setComment('转存金额'))
        ->addColumn(Column::decimal('profit',10,2)->setComment('转前当时的收益'))
        ->addColumn(Column::decimal('stock',10,2)->setComment('转后当时的库存'))
        ->addColumn(Column::integer('create_time')->setComment('提交时间'))
        ->addColumn(Column::integer('is_del')->setDefault(0)->setComment('是否删除'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('profit_to_stock_log');
    }
}
