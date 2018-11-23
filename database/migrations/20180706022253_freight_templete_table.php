<?php

use think\migration\Migrator;
use think\migration\db\Column;

class FreightTempleteTable extends Migrator
{
    // 运费模板表
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
        $this->table('freight_templete')
        ->addColumn(column::string('name')->setComment('模板名称'))
        ->addColumn(column::string('express_rule_ids')->setDefault('')->setComment('运费规则ID串'))
        ->addColumn(column::integer('is_valid')->setDefault(1)->setComment('是否启用:1是0否'))
        ->addColumn(column::integer('is_del')->setDefault(0)->setComment('是否删除:1是0否'))
        ->addColumn(column::datetime('create_ctime')->setComment('创建时间'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('freight_templete');
    }
}
