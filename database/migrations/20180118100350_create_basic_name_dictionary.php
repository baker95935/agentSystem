<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateBasicNameDictionary extends Migrator
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
        $this->table('basic_name_dictionary',['engine'=>'MyISAM'])
        ->addColumn(Column::string('name')->setComment('字典名称'))
        ->addColumn(Column::string('value')->setUnique()->setComment('名称对应的值'))
   		->addColumn(Column::integer('create_time')->setComment('创建时间'))
	    ->addColumn(Column::integer('update_time')->setComment('更新时间'))
        ->create(); 
    }
     public function down()
    {
        $this->dropTable('basic_name_dictionary');
    }
}
