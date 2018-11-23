<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWithdrawalsLogTable extends Migrator
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
    /**
     * 提现申请记录表
     */
    public function up()
    {
        $this->table('withdrawals_log',['engine'=>'MyISAM'])
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::tinyInteger('audit')->setDefault(1)->setComment('1待审,2已审,3驳回'))
        ->addColumn(Column::decimal('money',10,2)->setDefault(0)->setComment('提现额'))
        ->addColumn(Column::decimal('change_before',10,2)->setDefault(0)->setComment('变动前金额'))
        ->addColumn(Column::tinyInteger('type')->setComment('账户类型1支付宝,2银行卡'))
        ->addColumn(Column::dateTime('create_ctime')->setComment('申请日期'))
        ->addColumn(Column::dateTime('audit_atime')->setComment('审核日期'))
        ->addColumn(Column::string('remark')->setComment('备注'))
        ->addColumn(Column::string('result')->setComment('结果'))
        ->addColumn(Column::string('auditer')->setComment('审核人'))
        ->addColumn(Column::string('account_bank')->setComment('开户行'))
        ->addColumn(Column::string('account_name')->setComment('姓名'))
        ->addColumn(Column::string('account')->setComment('账号'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('withdrawals_log');
    }
}
