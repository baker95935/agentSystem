<?php

use think\migration\Migrator;
use think\migration\db\Column;

/* 购物车表 */
class CreateAgentCartTable extends Migrator
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
        $this->table('agent_cart')
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::integer('pid')->setComment('产品ID'))
        ->addColumn(Column::integer('num')->setComment('产品数量'))
        ->addColumn(column::integer('create_time')->setComment('添加时间'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('agent_cart');
    }
}
