<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentChangeRoleTable extends Migrator
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
        $this->table('agent_change_role')
        ->addColumn(column::integer('create_time')->setComment('创建时间'))
        ->addColumn(column::integer('agent_id')->setComment('agent_id'))
        ->addColumn(column::integer('before_role')->setComment('变动之前的role'))
        ->addColumn(column::integer('after_role')->setComment('变动之后的role'))
        ->addColumn(column::tinyInteger('reason')->setDefault(1)->setComment('1库存不足自动降级2手工调整级别'))
        ->addColumn(column::tinyInteger('type')->setDefault(1)->setComment('1降级2升级'))
        ->addColumn(Column::string('remark')->setComment('备注'))
        ->create();
    }

	public function down()
    {
        $this->dropTable('agent_change_role');
    }
}
