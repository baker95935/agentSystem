<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateProductManyImgTable extends Migrator
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
        $this->table('product_many_img',['engine'=>'MyISAM'])
            ->addColumn(Column::string('name')->setComment('图片名称'))
            ->addColumn(Column::string('img')->setComment('产品多图片地址'))
            ->addColumn(Column::integer('size')->setComment('图片大小'))
            ->addColumn(Column::integer('time')->setComment('添加时间'))
            ->addColumn(Column::integer('product_id')->setComment('产品id'))
            ->create();
    }
    public function down()
    {
        $this->dropTable('product_many_img');
    }
}
