<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentProfitTable extends Migrator
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
        $this->table('agent_profit')
        ->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        
        ->addColumn(Column::decimal('profit',10,2)->setDefault(0)->setComment('收益'))
        ->addColumn(column::string('order_number')->setComment('订单编号'))
        
        ->addColumn(Column::decimal('sales_amount',10,2)->setDefault(0)->setComment('销售额'))
        
        ->addColumn(Column::tinyInteger('type')->setComment('收益类型1直销奖励2间接奖励3招商奖励4绩效奖励5礼包奖励6代理直接奖励7下级升级奖励8下级充值库存奖励'))
        ->addColumn(Column::tinyInteger('son_type')->setDefault(1)->setComment('子收益类型1库存充值奖励-下级升级2库存充值奖励-库存充值3招商奖励-下级升级4招商奖励-库存充值'))
        ->addColumn(column::string('relation_id')->setComment('关联编号'))
        ->addColumn(column::integer('month')->setComment('月份'))
        ->addColumn(column::integer('year')->setComment('年份'))
        ->addColumn(column::integer('product_id')->setComment('产品ID'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_profit');
    }
}
