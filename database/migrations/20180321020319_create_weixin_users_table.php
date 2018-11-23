<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWeixinUsersTable extends Migrator
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
        $this->table('weixin_users')
        ->addColumn(Column::string('openid')->setComment('openid'))
        ->addColumn(column::integer('agent_id')->setComment('代理商表的ID'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::string('nickname')->setComment('昵称'))
        ->addColumn(Column::string('headimgurl',1024)->setComment('头像'))
        ->addColumn(column::string('city')->setComment('城市'))
        ->addColumn(column::string('province')->setComment('省份'))
        ->addColumn(column::string('country')->setComment('国家'))
        ->addColumn(column::string('scope')->setComment('scope类型'))
        ->addColumn(column::tinyInteger('sex')->setComment('1男性2女性0未知'))
        ->create();
    }

	public function down()
    {
        $this->dropTable('weixin_users');
    }
}
