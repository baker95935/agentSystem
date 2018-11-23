<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderSettingFreightTable extends Migrator
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
    	$this->table('agent_order_setting_freight')
    	
    	->addColumn(Column::integer('role')->setDefault(1)->setComment('角色身份'))
    	->addColumn(Column::decimal('order_amount',10,2)->setDefault(0)->setComment('订单额度'))
    	->addColumn(Column::integer('freight')->setDefault(1)->setComment('运费'))
    	->addColumn(Column::integer('create_time')->setDefault(1)->setComment('创建时间'))
    	
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('agent_order_setting_freight');
    }
}
