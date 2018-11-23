<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderConsigneeAddressTable extends Migrator
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
        $this->table('agent_order_consignee_address')
        ->addColumn(Column::string('order_number')->setUnique()->setComment('订单编号'))
   		->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
	    ->addColumn(Column::integer('create_time')->setDefault(1)->setComment('创建时间'))
	    
	    ->addColumn(Column::string('consignee_name')->setComment('收货人姓名'))
	    ->addColumn(Column::string('consignee_phone')->setComment('收货人电话'))
	    ->addColumn(Column::string('province')->setComment('省份'))
	    ->addColumn(Column::string('city')->setComment('城市'))
	    ->addColumn(Column::string('area')->setComment('地区'))
	    ->addColumn(Column::string('address')->setComment('收货人详细地址'))
        ->create(); 
    }
     public function down()
    {
        $this->dropTable('agent_order_consignee_address');
    }
}
