<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentAddressTable extends Migrator
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

    /* 代理商地址表 */

    public function up()
    {
        $this->table('agent_address')
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::string('name')->setComment('收货人姓名'))
        ->addColumn(Column::string('phone')->setComment('收货人电话'))
        ->addColumn(Column::integer('province')->setComment('省份'))
        ->addColumn(Column::integer('city')->setComment('城市'))
        ->addColumn(Column::integer('area')->setComment('地区'))
        ->addColumn(Column::string('address')->setComment('收货人详细地址'))
        ->addColumn(Column::integer('is_default')->setDefault(0)->setComment('是否默认'))
        ->addColumn(Column::integer('is_del')->setDefault(0)->setComment('是否删除'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('agent_address');
    }
}
