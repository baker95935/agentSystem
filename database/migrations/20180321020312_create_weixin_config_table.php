<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateWeixinConfigTable extends Migrator
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
        $this->table('weixin_config')
        ->addColumn(Column::string('appid')->setComment('appid'))
        ->addColumn(column::string('secret')->setComment('secret'))
        ->addColumn(Column::string('access_token',1024)->setComment('access_token'))
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::integer('expires_time')->setComment('过期时间'))
        
        ->create();
    }

	public function down()
    {
        $this->dropTable('weixin_config');
    }
}
