<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateProductManagementTable extends Migrator
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
    /*产品添加管理表*/
    public function up()
    {
        $this->table('product_management',['engine'=>'InnoDB'])
        ->addColumn(Column::string('product_name')->setComment('产品名称'))

        ->addColumn(Column::integer('category_id')->setComment('类目管理ID'))
        ->addColumn(Column::integer('classify_id')->setComment('分类管理ID'))

        ->addColumn(Column::string('product_img')->setDefault('/static/Images/default_product.jpg')->setComment('封面图片'))
        ->addColumn(Column::string('explain')->setComment('封面图片简短说明'))



        ->addColumn(Column::decimal('sales_price',10,2)->setDefault(0)->setComment('销售价格'))
        ->addColumn(Column::decimal('cost_price',10,2)->setDefault(0)->setComment('成本价格'))
        ->addColumn(Column::string('unit')->setComment('单位'))
        ->addColumn(Column::decimal('weight',10,2)->setDefault(0)->setComment('重量'))
        ->addColumn(Column::integer('inventory')->setComment('库存'))
        ->addColumn(Column::integer('sales_volume')->setComment('产品销量'))


        ->addColumn(Column::string('details')->setComment('详情说明'))
        ->addColumn(Column::string('specification')->setComment('产品规格'))

        ->addColumn(Column::tinyInteger('state')->setDefault(1)->setComment('1上架,0下架'))
        ->addColumn(Column::tinyInteger('exemption_from_postage')->setDefault(1)->setComment('1包邮,0不包邮'))
        ->addColumn(Column::tinyInteger('is_gift')->setDefault(1)->setComment('是否大礼包产品1是,其他否'))
        ->addColumn(Column::tinyInteger('is_first_order')->setDefault(1)->setComment('是否首次下单产品1是,其他否'))

        ->addColumn(Column::tinyInteger('is_Purchase_a')->setDefault(1)->setComment('是否限购一件1是, 2否'))
        ->addColumn(Column::tinyInteger('is_agent_level')->setDefault(1)->setComment('是否代理商身份0否，其他是对应等级的数字'))

        ->addColumn(Column::integer('create_time')->setComment('产品添加时间'))
        ->addColumn(Column::integer('end_time')->setComment('产品下架时间'))
        ->addColumn(Column::integer('putaway_time')->setComment('产品上架时间'))
        ->addColumn(Column::integer('false_volume')->setComment('产品售出设置'))
        ->addColumn(Column::integer('express')->setDefault(0)->setComment('运费模板'))
        ->create();
    }
    public function down()
    {
        $this->dropTable('product_management');
    }
}
