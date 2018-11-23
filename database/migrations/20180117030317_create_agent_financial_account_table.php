<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentFinancialAccountTable extends Migrator
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
    /* 代理商金融账号绑定信息表 */
    public function up()
    {
        $this->table('agent_financial_account',['engine'=>'MyISAM'])
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::dateTime('create_ctime')->setComment('注册时间'))
        ->addColumn(Column::integer('type')->setDefault('0')->setComment('账户类型:0默认未知1支付宝2银行卡3微信支付'))
        ->addColumn(Column::string('account')->setComment('账号'))
        ->addColumn(Column::tinyInteger('is_del')->setDefault('0')->setComment('是否删除:0否1是'))
        ->addColumn(Column::tinyInteger('is_default')->setDefault('0')->setComment('是否默认账户:0否1是'))
        ->addColumn(Column::string('bank')->setComment('开户行名称'))
        ->addColumn(Column::string('name')->setComment('姓名'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('agent_financial_account');
    }
}
