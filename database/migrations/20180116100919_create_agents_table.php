<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateAgentsTable extends Migrator
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
    /* 代理商基本信息表 */
    public function up()
    {
        $this->table('agents',['engine'=>'InnoDB','auto_increment'=>'100000'])
        ->setId('agent_id')
        ->addColumn(Column::string('nickname')->setComment('名称'))
        ->addColumn(Column::string('wechat')->setComment('微信号'))
        ->addColumn(Column::string('password')->setComment('密码'))
        ->addColumn(Column::string('phone')->setComment('登录账号/手机号'))
        ->addColumn(Column::dateTime('create_ctime')->setComment('注册时间'))
        ->addColumn(Column::string('end_etime')->setDefault('-1')->setComment('截止日期,-1为永久'))
        ->addColumn(Column::integer('generation')->setDefault('1')->setComment('代数'))
        ->addColumn(Column::integer('role')->setDefault('0')->setComment('角色:0会员1铜2银3金4钻石5皇冠6分公司'))
        ->addColumn(Column::string('inviter')->setComment('邀请人'))
        ->addColumn(Column::string('family')->setComment('上级族谱'))
        ->addColumn(Column::string('name')->setComment('姓名'))
        ->addColumn(Column::enum('sex', ['m','w','s'])->setDefault('m')->setComment('性别:m男w女s保密'))
        ->addColumn(Column::string('id_card')->setComment('身份证号'))
        ->addColumn(Column::integer('province')->setComment('省'))
        ->addColumn(Column::integer('city')->setComment('市'))
        ->addColumn(Column::integer('area')->setComment('区/县'))
        ->addColumn(Column::string('address')->setComment('详细地址'))
        ->addColumn(Column::tinyInteger('is_del')->setDefault('0')->setComment('是否删除:0否1是'))
        ->addColumn(Column::tinyInteger('is_use')->setDefault('0')->setComment('是否启用:0否1是'))
        ->addColumn(Column::decimal('stock_money',10,2)->setDefault(0)->setComment('库存金额'))
        ->addColumn(Column::integer('status')->setDefault(0)->setComment('代理商状态:0默认1申请注册2申请升级3已确认4驳回5取消'))
        ->addColumn(Column::string('qq')->setComment('qq号'))
        ->addColumn(Column::string('head_img')->setDefault('/static/Images/default_head.png')->setComment('头像图片'))
        ->addColumn(Column::string('qr_code_img')->setComment('二维码图片'))
        ->addColumn(Column::decimal('profit',10,2)->setDefault(0)->setComment('收益金额'))

        ->create();
    }

    public function down()
    {
        $this->dropTable('agents');
    }
}
