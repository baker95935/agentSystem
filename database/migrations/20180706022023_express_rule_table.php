<?php

use think\migration\Migrator;
use think\migration\db\Column;

class ExpressRuleTable extends Migrator
{
    // 运费规则表
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
        $this->table('express_rule')
        ->addColumn(column::string('name')->setComment('名称'))
        ->addColumn(column::integer('type')->setDefault('1')->setComment('计价类型:1按件2按重'))
        ->addColumn(column::decimal('first_num',10,2)->setDefault(1)->setComment('首件件数/首重重量'))
        ->addColumn(column::decimal('first_price',10,2)->setDefault(0)->setComment('首件/首重费用'))
        ->addColumn(column::decimal('continue_num',10,2)->setDefault(1)->setComment('续件件数/续重重量'))
        ->addColumn(column::decimal('continue_price',10,2)->setDefault(0)->setComment('续件/续重费用'))
        ->addColumn(column::decimal('free_num',10,2)->setDefault(0)->setComment('按件免邮/按重免邮'))
        ->addColumn(column::integer('is_inside')->setDefault(1)->setComment('区域模式:1域内0域外'))
        ->addColumn(column::decimal('cost',10,2)->setDefault(0)->setComment('此规则所需满足金额:0无限制'))
        ->addColumn(column::text('province')->setComment('选择的省级区域'))
        ->addColumn(column::text('city')->setComment('选择的市级区域'))
        ->addColumn(column::text('area')->setComment('选择的县级区域'))
        ->addColumn(column::datetime('create_ctime')->setComment('创建时间'))
        ->addColumn(column::integer('is_valid')->setDefault('1')->setComment('是否有效:1有效0无效'))
        ->addColumn(column::integer('is_del')->setDefault('0')->setComment('是否删除:1是0否'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('express_rule');
    }
}
