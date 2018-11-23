<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ExpressCompanyTable extends Migrator
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
        $this->table('express_company')
        ->addColumn(Column::string('name')->setComment('物流公司名称'))
        ->addColumn(Column::string('code')->setComment('对应的查询代码'))
        ->addColumn(column::datetime('create_ctime')->setComment('创建时间'))
        ->addColumn(column::integer('is_del')->setDefault(0)->setComment('是否删除'))
        ->addColumn(column::integer('is_use')->setDefault(1)->setComment('是否启用'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('express_company');
    }
}