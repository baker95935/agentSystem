<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentRewardAgencyTable extends Migrator
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
    	$this->table('agent_reward_agency')
    	->addColumn(Column::integer('role')->setDefault(0)->setComment('代理商等级角色'))
    	->addColumn(Column::decimal('ratio',10,2)->setDefault(0)->setComment('奖励比例%'))
    	->addColumn(Column::decimal('pre_deposit',10,2)->setDefault(0)->setComment('预存款额度'))
    	->addColumn(Column::decimal('first_goods_number',10,2)->setDefault(0)->setComment('首次拿货数量%'))
    	->addColumn(Column::decimal('first_goods_money',10,2)->setDefault(0)->setComment('首次拿货金额'))
    	->addColumn(Column::decimal('lowest_limit',10,2)->setDefault(0)->setComment('最低库存额度'))
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('agent_reward_agency');
    }
}
