<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentRewardConfigTable extends Migrator
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
    	$this->table('agent_reward_config',['engine'=>'MyISAM'])
    	->addColumn(Column::string('recommend_reward_name')->setDefault('推荐奖励')->setComment('推荐奖励名称'))
    	->addColumn(Column::tinyInteger('valid_recommend_reward')->setDefault(1)->setComment('是否开启推荐奖励1开启2关闭'))
    	->addColumn(Column::string('agency_reward_name')->setDefault('代理奖励')->setComment('代理奖励名称'))
    	->addColumn(Column::tinyInteger('valid_agency_reward')->setDefault(1)->setComment('是否开启代理奖励1开启2关闭'))
    	->addColumn(Column::string('performance_reward_name')->setDefault(1)->setDefault('业绩奖励')->setComment('业绩奖励名称'))
    	->addColumn(Column::tinyInteger('valid_performance_reward')->setComment('是否开启业绩奖励1开启2关闭'))
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('agent_reward_config');
    }
}
