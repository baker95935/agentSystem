<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentAchievementTopTable extends Migrator
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
        $this->table('agent_achievement_top')
        ->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        
        ->addColumn(Column::integer('rank')->setComment('排名'))
        
        ->addColumn(Column::decimal('profit',10,2)->setDefault(0)->setComment('收益'))
        
        ->addColumn(Column::tinyInteger('type')->setComment('收益类型1总收益2总绩效3总推荐人数4总销售排行'))
        ->addColumn(column::integer('month')->setComment('月份'))
        ->addColumn(column::integer('year')->setComment('年份'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_achievement_top');
    }
}
