<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentLevelTable extends Migrator
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
    	$this->table('agent_level',['engine'=>'MyISAM'])
    	->addColumn(Column::string('name')->setComment('名称'))
    	->addColumn(Column::Integer('deep')->setDefault(2)->setComment('推荐奖励深度'))
    	->addColumn(Column::tinyInteger('show')->setDefault(1)->setComment('是否显示'))
    	->addColumn(Column::string('valid')->setDefault(1)->setComment('是否可用'))
    	->create();
    }
    
    public function down()
    {
    	$this->dropTable('agent_level');
    }
}
