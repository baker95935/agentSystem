<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentPerformanceRewardTable extends Migrator
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
        $this->table('agent_performance_reward')
        ->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        
        ->addColumn(column::integer('month')->setComment('月份'))
        ->addColumn(column::integer('year')->setComment('年份'))
        
        ->addColumn(Column::decimal('performance_profit',10,2)->setDefault(0)->setComment('本月业绩分红'))
        ->addColumn(Column::decimal('increate_ratio',10,2)->setDefault(0)->setComment('本月增长比例/系数'))
        
        ->addColumn(Column::decimal('last_performance_base',10,2)->setDefault(0)->setComment('上月业绩分红基数'))
        ->addColumn(Column::decimal('current_performance_base',10,2)->setDefault(0)->setComment('本月业绩分红基数'))
        
        ->addColumn(Column::decimal('current_agent_profit',10,2)->setDefault(0)->setComment('本月代理收入'))
        ->addColumn(Column::decimal('current_recommend_profit',10,2)->setDefault(0)->setComment('本月招商奖励'))
        ->addColumn(Column::decimal('current_promotion_gift_profit',10,2)->setDefault(0)->setComment('本月大礼包奖励'))
        
        ->addColumn(Column::tinyInteger('status')->setComment('结算类型1未结算2已结算3失效'))
        ->addColumn(column::integer('update_time')->setComment('更新时间'))
        ->addColumn(column::string('remark')->setComment('备注'))
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_performance_reward');
    }
}
