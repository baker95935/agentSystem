<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateProductRecommendRewardTable extends Migrator
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
        $this->table('product_recommend_reward')
            ->addColumn(Column::integer('role')->setComment('招商奖励身份'))
            ->addColumn(Column::decimal('value',10,2)->setDefault(0)->setComment('招商奖励值'))
            ->addColumn(Column::integer('hierarchy')->setComment('等级制度'))
            ->addColumn(Column::integer('product_id')->setComment('产品id'))
            ->create();
    }

    public function down()
    {
        $this->dropTable('product_recommend_reward');
    }

}
