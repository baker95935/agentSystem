<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderSettingTable extends Migrator
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
    	$this->table('agent_order_setting')
    	
    	->addColumn(Column::integer('auto_confirm_time')->setDefault(1)->setComment('自动确认收货时间'))
    	->addColumn(Column::tinyInteger('consignment_info')->setDefault(1)->setComment('1公司2上级代理3俩个都选'))
    	->addColumn(Column::integer('time_span')->setDefault(1)->setComment('时长'))
    	->addColumn(Column::string('return_goods_address')->setComment('退货地址'))
    	
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('agent_order_setting');
    }
}
