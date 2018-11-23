<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateProductCategoryTable extends Migrator
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
        $this->table('product_category',['engine'=>'MyISAM'])
        ->addColumn(Column::string('category_name')->setComment('分类名称'))
        ->addColumn(Column::integer('parent_id')->setComment('父ID'))
        ->addColumn(Column::integer('category_img')->setComment('类目图片'))
        ->addColumn(Column::integer('create_time')->setComment('添加时间'))
        ->addColumn(Column::integer('level_type')->setComment('等级'))
        ->create(); 
    }
     public function down()
    {
        $this->dropTable('product_category');
    }
}
