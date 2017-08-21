<?php

namespace app\models\eac;

use Yii;

/**
 * This is the model class for table "rw_rwsl".
 *
 * @property string $rw_rwsl_id
 * @property string $aj_ajxxb_id
 * @property string $chuangjianr
 * @property string $chuangjiansj
 * @property string $zhixingr
 * @property string $zhixingsj
 * @property string $rw_rwdy_id
 * @property integer $modtime
 */
class Rwsl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rw_rwsl';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('dbEAC');
    }

    public static function rwdyIdMappingContent()
    {
        return [
            'notdefined' => '未定义',
            '01' => '客户信息复核',
            '02'=>'立案采集',
            '020'=>'文件抄送',
            '03'=>'立案复核',
            '031'=>'新案提交',
            '04'=>'新案分发',
            '05'=>'新案办案',
            '051'=>'新案文件制备',
            '052'=>'撰写任务',
            '053'=>'制图任务',
            '054'=>'翻译任务',
            '06'=>'新案质检',
            '061'=>'等待受理通知书',
            '062'=>'新案实体复核',
            '07'=>'新申请受理通知',
            '071'=>'缴纳申请费',
            '08'=>'中间文件质检',
            '082'=>'中间文件实体复核',
            '09'=>'中间文件办案',
            '091'=>'中间文件制备',
            '092'=>'优先权转让提交监控',
            '0A5J0CVDKV010006'=>'初审答复',
            '0A690676BV010001'=>'视撤恢复',
            '0A6906H202010003'=>'缴纳视撤恢复费',
            '0A750S553J010276'=>'通知书转达',
            '0A750U1LBH01029B'=>'视为未提出',
            '0A750UTRDO0102CD'=>'手续合格通知书',
            '0A750V76NS0102D8'=>'优先权恢复',
            '0A750V821T0102D9'=>'缴纳优先权恢复费',
            '0A7510QCRQ010322'=>'视放恢复',
            '0A7510RL7A010325'=>'缴纳视放恢复费',
            '0A75114FVL01032A'=>'缴纳年费',
            '0A7605G6NE010351'=>'终止恢复',
            '0A7605JABT010354'=>'缴纳终止恢复费',
            '0A7606LMMR010364'=>'主动补正',
            '0A76074G2Q01037B'=>'实审答N通',
            '0A760O8GN5010752'=>'缴费确认',
            '0A7H0L8D0A010359'=>'案件修改',
            '0A7H0LGAE601035F'=>'案卷修改',
            '0A7H0M0EBU010397'=>'手续补正',
            '0A7H0MC9UE0103A6'=>'特殊修改',
            '0A7H0MS14F01041A'=>'复审权利恢复',
            '0A7H0MTENU01043B'=>'复审请求恢复',
            '0A7H0NL2NR010497'=>'诉讼请求',
            '0A7H0O3APJ0104CB'=>'香港注册第一阶段',
            '0A7H0O3SC30104EB'=>'香港注册第二阶段',
            '0A7H0OH1L2010557'=>'实审答复',
            '0A7H0P3B000105A1'=>'澳门注册',
            '0EC20DM4VS01003F'=>'答复补正期限',
            '0EC20NL6EG010068'=>'答复一通期限',
            '0EC20NOCJH01006F'=>'答复N通期限',
            '0EC20NUAAB010080'=>'改正译文错误通知书期限',
            '0EC20NVHVC010084'=>'答复《无效宣告请求受理通知书》期限',
            '0EC20O088O010088'=>'答复《无效宣告请求补正通知书》',
            '0EC20O15JN01008C'=>'答复《复审请求补正通知书》期限',
            '0EC90TRLUH01033F'=>'缴纳申请费期限',
            '10'=>'初审补正',
            '107F0JQ2BF010645'=>'专利受理通知书',
            '11'=>'等待提复审',
            '112L04S7C801512D'=>'初审补正',
            '112L04T0TL015136' =>'',
            '12'=>'等待提实审',
            '121'=>'缴纳实审费',
            '13'=>'实审答OA1',
            '14'=>'缴纳年登印费',
            '15'=>'复审补正',
            '16' => '复审答OA',
            '17' => '客户来文查阅',
            '18' => '客户咨询',
            '19' => '确认缴费',
            '21' => '提交复审请求',
            'custom' => '自定义类型',
            'file' => '新增专利文件'
        ];
    }

}
