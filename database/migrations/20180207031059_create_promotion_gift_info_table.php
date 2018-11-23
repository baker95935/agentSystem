<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePromotionGiftInfoTable extends Migrator
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
        $this->table('promotion_gift_info')
        ->addColumn(Column::string('name')->setComment('礼包名称'))
        ->addColumn(column::tinyInteger('type')->setComment('礼包类型'))
        ->addColumn(Column::decimal('price',10,2)->setDefault(0)->setComment('价格'))
        ->addColumn(Column::integer('number')->setDefault(0)->setComment('数量'))
        ->addColumn(Column::integer('sale')->setDefault(0)->setComment('已售'))
        
         ->addColumn(column::string('description')->setComment('说明 '))
         ->addColumn(column::string('pic')->setComment('封面图片 '))
         ->addColumn(Column::integer('create_time')->setDefault(0)->setComment('创建时间'))
         ->addColumn(Column::integer('product_id')->setComment('礼包关联的商品'))
         
        ->create();
    }

	public function down()
    {
        $this->dropTable('promotion_gift_info');
    }
}
