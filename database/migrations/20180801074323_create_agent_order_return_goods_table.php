<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentOrderReturnGoodsTable extends Migrator
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
        $this->table('agent_order_return_goods')
         ->addColumn(Column::string('order_number')->setComment('订单编号'))
         ->addColumn(column::integer('create_time')->setComment('创建时间'))
         

         ->addColumn(column::integer('agent_id')->setComment('agent_id'))
         
         ->addColumn(Column::string('remark')->setComment('退货备注'))
         ->addColumn(Column::string('express_name')->setComment('快递公司名称'))
         ->addColumn(Column::string('express_number')->setComment('物流编号'))
         ->addColumn(Column::string('express_code')->setComment('物流公司代码'))
         
         ->addColumn(column::integer('auth_time')->setComment('审核时间'))
         ->addColumn(column::tinyInteger('auth_status')->setDefault(1)->setComment('审核状态1未审核2审核通过3审核拒绝'))
         ->addColumn(column::string('author')->setComment('审核人'))
         ->addColumn(column::string('reason')->setComment('理由'))
         
         ->addColumn(column::integer('financial_time')->setComment('财务审核时间'))
         ->addColumn(column::tinyInteger('financial_status')->setDefault(1)->setComment('审核状态1未审核2审核通过'))
         ->addColumn(column::string('financial_author')->setComment('财务审核人'))
         ->addColumn(column::string('financial_reason')->setComment('财务理由'))
         
         ->addColumn(column::string('agent_order_refund_id')->setComment('关联的订单退款表'))
          
	 
        ->create();
    }

    public function down()
    {
        $this->dropTable('order_return_goods');
    }
}
