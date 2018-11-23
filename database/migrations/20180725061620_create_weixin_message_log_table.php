<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWeixinMessageLogTable extends Migrator
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
        $this->table('weixin_message_log')
        ->addColumn(column::string('openid')->setComment('openid'))
        ->addColumn(column::integer('agent_id')->setComment('agent_id'))
        ->addColumn(column::integer('type')->setComment('消息类型1购买成功通知2订单标记发货通知3账户变更通知'))
        ->addColumn(column::string('content')->setDefault(1)->setComment('内容'))
        ->addColumn(column::integer('create_ctime')->setComment('创建时间'))
        ->addColumn(column::string('remark')->setComment('发送结果'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('weixin_message_log');
    }
}
