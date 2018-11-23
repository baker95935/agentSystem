<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentStockChangeTable extends Migrator
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
        $this->table('agent_stock_change')
        ->addColumn(Column::integer('agent_id')->setComment('代理商ID'))
        ->addColumn(column::integer('create_time')->setComment('申请时间'))
        ->addColumn(Column::decimal('change_before',10,2)->setDefault(0)->setComment('变动前金额'))
        ->addColumn(Column::decimal('change_after',10,2)->setDefault(0)->setComment('变动后金额'))
        ->addColumn(Column::decimal('money',10,2)->setDefault(0)->setComment('变动金额'))
        ->addColumn(Column::tinyInteger('status')->setComment('审核状态1待审核2已审核3驳回'))
        ->addColumn(Column::tinyInteger('account_type')->setComment('账号类型1微信2支付宝3银行卡4系统后台充值5其他'))
        ->addColumn(Column::tinyInteger('change_type')->setComment('变动类型1申请充值2后台充值3减库存4加库存5下级升值库存充值减库存6用户升级加库存7下级充值库存减库存8购买商品充值库存9下级转账减库存10上级转账加库存'))
        ->addColumn(Column::string('order_number')->setComment('订单号'))
        ->addColumn(column::string('remark')->setComment('备注'))
        ->addColumn(column::integer('audit_time')->setComment('审核时间'))
        ->addColumn(column::integer('auditor_id')->setComment('审核人'))
        ->addColumn(Column::integer('product_id')->setComment('对应的产品ID'))
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_stock_change');
    }
}
