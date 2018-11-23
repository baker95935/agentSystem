<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateBasicDataAddressTable extends Migrator
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
    /* 三级地址基础数据表 */
    public function up()
    {
        $this->table('basic_data_address',['engine'=>'MyISAM'])
        ->addColumn(Column::string('name')->setComment('名称'))
        ->addColumn(Column::string('parent_id')->setComment('上级ID'))
        ->addColumn(Column::string('short_name')->setComment('短名称'))
        ->addColumn(Column::integer('level_type')->setComment('级别'))
        ->addColumn(Column::integer('city_code')->setComment('城市代码'))
        ->addColumn(Column::integer('zip_code')->setComment('邮编'))
        ->addColumn(Column::string('merger_name')->setComment('总称'))
        ->addColumn(Column::decimal('lng',12,8)->setComment('经度'))
        ->addColumn(Column::decimal('lat',12,8)->setComment('维度'))
        ->addColumn(Column::string('pinyin')->setComment('拼音'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('basic_data_address');
    }
}
