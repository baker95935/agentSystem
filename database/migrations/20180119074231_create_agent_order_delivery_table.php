<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderDeliveryTable extends Migrator
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
        $this->table('agent_order_delivery')
        ->addColumn(Column::string('order_number')->setUnique()->setComment('订单编号'))
   		->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
	    ->addColumn(Column::integer('create_time')->setDefault(1)->setComment('创建时间'))
	    
	    ->addColumn(Column::tinyInteger('type')->setDefault(1)->setComment('配送方式1是快递2是自提'))
	    ->addColumn(Column::string('remark')->setComment('发货备注'))
	    ->addColumn(Column::string('express_name')->setComment('快递公司名称'))
	    ->addColumn(Column::string('express_number')->setComment('物流编号'))
	    ->addColumn(Column::string('express_code')->setComment('物流公司代码'))
        ->create(); 
    }
     public function down()
    {
        $this->dropTable('agent_order_delivery');
    }
}
