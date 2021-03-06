<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePromotionGiftOrderRewardTable extends Migrator
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
    	$this->table('promotion_gift_order_reward')
    	
    	->addColumn(Column::string('order_number')->setComment('订单编号'))
    	->addColumn(Column::integer('agent_id')->setComment('享受奖励的代理商ID'))
    	->addColumn(Column::integer('create_time')->setDefault(1)->setComment('创建时间'))
    	 
    	->addColumn(Column::decimal('recommend_reward',10,2)->setDefault(0)->setComment('推荐奖励'))
    	->addColumn(Column::integer('recommend_hierarchy')->setDefault(0)->setComment('推荐奖励层级'))
    	 
    	->addColumn(Column::tinyInteger('status')->setDefault(1)->setComment('结算状态1未结算2已结算3失效'))
    	->addColumn(Column::integer('update_time')->setDefault(1)->setComment('更新时间'))
    	
    	->addColumn(Column::integer('reward_type')->setComment('奖励类型1是推荐奖励'))
    	->addColumn(Column::integer('gift_id')->setComment('享受奖励的产品ID'))
    	->addColumn(Column::string('remark')->setComment('备注'))
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('promotion_gift_order_reward');
    }
}
