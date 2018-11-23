<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AgentsDataCountForRank extends Migrator
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
    /**
     * 代理商排行榜表(主键a_id设置为不自增)
     * @return [type] [description]
     */
    public function up()
    {
        $this->table('agents_data_count_for_rank')
        ->setId('a_id',false)
        ->addColumn(column::decimal('sales_money',10,2)->setDefault(0)->setComment('销售总额'))
        ->addColumn(column::decimal('rewards_money',10,2)->setDefault(0)->setComment('奖励总额'))
        ->addColumn(Column::integer('recommend_num')->setDefault(0)->setComment('推荐人数'))
        ->addColumn(Column::integer('team_num')->setDefault(0)->setComment('直属团队'))
        ->addColumn(Column::integer('team_rank')->setComment('团队排名'))
        ->addColumn(Column::integer('recommend_rank')->setComment('推荐排名'))
        ->addColumn(Column::integer('reward_rank')->setComment('奖励排名'))
        ->addColumn(Column::integer('sale_rank')->setComment('销量排名'))
        ->addColumn(column::integer('is_del')->setDefault(0)->setComment('是否删除: 0否 1是'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('agents_data_count_for_rank');
    }
}
