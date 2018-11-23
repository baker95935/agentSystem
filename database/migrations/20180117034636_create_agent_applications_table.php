<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentApplicationsTable extends Migrator
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
    /* 代理商综合申请信息表(注册/升级/变更) */
    public function up()
    {
        $this->table('agent_applications',['engine'=>'MyISAM'])
        ->addColumn(Column::integer('a_id')->setComment('代理商ID'))
        ->addColumn(Column::integer('type')->setComment('申请类型:0默认无效1注册2升级3变更4公司录入'))
        ->addColumn(Column::dateTime('create_ctime')->setComment('申请时间'))
        ->addColumn(Column::integer('target')->setComment('申请目标:是否通过/目标等级/变更目标编号'))
        ->addColumn(Column::integer('status')->setDefault('0')->setComment('审核状态:0待审核1已通过2已驳回3已取消'))
        ->addColumn(Column::dateTime('examine_etime')->setComment('审核时间'))
        ->addColumn(Column::string('examiner')->setComment('审核人'))
        ->addColumn(Column::string('remarks')->setComment('备注信息'))
        ->addColumn(Column::tinyInteger('is_del')->setDefault('0')->setComment('是否删除:0否1是'))

        ->addColumn(Column::integer('now')->setDefault(-1)->setComment('当前角色身份|当前上级ID'))
        ->addColumn(Column::integer('now_g')->setDefault(-1)->setComment('当前代数:-1无变更'))
        ->addColumn(Column::integer('target_g')->setDefault(-1)->setComment('变更后代数:-1无变更'))
        ->addColumn(Column::decimal('money',10,2)->setDefault(0)->setComment('支付金额'))
        ->addColumn(Column::string('img')->setComment('交易单'))
        ->addColumn(Column::integer('apply_by_id')->setComment('代申请人ID'))
        ->addColumn(Column::string('examine_remark')->setComment('驳回备注'))
        ->create();
    }

    public function down()
    {
        $this->dropTable('agent_applications');
    }
}
