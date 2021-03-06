<?php
use app\models\UnpaidAnnualFee;
/* @var $patent app\models\Patents */
/* @var $idx integer */

/* @var $box_type string */
/* @var $show_fee boolean 是否展示费用信息按钮 */
/* @var $fee app\models\UnpaidAnnualFee 缴费信息 */

$box_type = 'box-default';
$show_fee = false;
$fee = [];
// $show_fee = true;
// $fee = UnpaidAnnualFee::findOne(['patentAjxxbID' => $patent['patentAjxxbID'], 'due_date' => $patent['patentFeeDueDate']]);
// //if ($patent['patentCaseStatus'] == '有效') {
//     // 15天之内红色 90天之内黄色 其他绿色
//     $diff_days = (int)date_diff(date_create(date('Ymd')),date_create($patent['patentFeeDueDate']))->format('%R%a');
//     if ($diff_days < 0) {
//         // TODO 过期
//     } elseif ($diff_days == 0) {
//         // TODO 今天到期
//     } elseif ($diff_days <= 15) {
//         $box_type = 'box-danger';
//     } elseif ($diff_days <= 90) {
//         $box_type = 'box-warning';
//     } else {
//         $box_type = 'box-success';
//         $show_fee = false; // 大于90天不展示续费按钮
//     }
// //} else {
// //    // TODO 非有效期
// //}
?>
<div class="box box-solid <?= $box_type ?> collapsed-box">
    <div class="box-header">
        <a href="javascript:void(0)" onclick="collapseToggle(<?= $idx ?>)" style="display: block">
<!--            <i class="fa fa-file-o"></i>-->
            <?= $idx ?> .
            <h3 class="box-title">
                <?= $patent['patentTitle'] . (isset($patent['patentUserParentFullname']) ? ('（' . $patent['patentUserParentFullname'] . '）') :'') ?>
            </h3>
        </a>

        <div class="box-tools pull-right">
            <button id="<?= $idx ?>"  type="button" class="btn btn-default btn-sm" data-widget="collapse"><i class="fa fa-plus"></i>
            </button>
            <a type="button" class="btn btn-default btn-sm" title="点击查看专利进度" href="<?= \yii\helpers\Url::to(['patents/main', 'id' => $patent['patentAjxxbID']]) ?>"><i class="fa fa-paper-plane"></i>
            </a>
        </div>
    </div>
    <div class="box-body" style="display: none">
        <dl>
            <dt>专利类型</dt>
            <dd><?= $patent['patentType'] ?></dd>
            <dt>创建时间</dt>
            <dd><?= Yii::$app->formatter->asDatetime($patent['UnixTimestamp'] / 1000) ?></dd>
<!--            <dt>主办人</dt>-->
<!--            <dd>--><?//= $patent['patentAgent'] ? ($patent['patentAgent'] . '(<a href="tel:' . $patent['agentContact']['userCellphone'] . '">' . $patent['agentContact']['userCellphone'] . '</a>)') : '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?><!--</dd>-->
            <dt>申请号</dt>
            <dd><?= $patent['patentApplicationNo'] ?: '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?></dd>
            <dt>申请日</dt>
            <dd><?= $patent['patentApplicationDate'] ?: '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?></dd>
        </dl>

    </div>

    <?php
    if ($fee && $show_fee) {
        echo '<div class="box-footer" style="display: block">';
        if ($this->context->isMicroMessage) {
            echo '<a class="btn btn-success btn-xs" id="pay-btn" data-id="' . $fee->patentAjxxbID . '">缴费('. $fee->fee_type . ':' . $fee->amount .'元)</a><div id="wxJS"></div>'; // TODO 如何给客户展示：颜色以及显示内容等等
        } else {
            echo '<a href="#" class="btn btn-success btn-xs" id="pay-btn" data-toggle="tooltip" data-html="true"  data-placement="right" title="<img src=\''.\yii\helpers\Url::to(["pay/wx-qrcode", "id"=>$fee->patentAjxxbID]).'\' />">缴费('. $fee->fee_type . ':' . $fee->amount .'元)</a>';
        }
        echo '</div>';
    }
    ?>

</div>
<?php
if ($this->context->isMicroMessage) {
    $this->registerJs('
$(\'#pay-btn\').click(function(){
	var url = "'. \yii\helpers\Url::to(["pay/payment"]).'";
        $.post(url, {pay_type:\'WXPAY\',id:$(this).data(\'id\')}, function(d) {
            if(d.done == true) {
                $(\'#wxJS\').html(d.data);
                callpay();
            }
        },\'json\');
    })
');
} else {
$this->registerCss('
.tooltip-inner {
    background-color: #fff;
    padding:0;
}
');
}

?>
