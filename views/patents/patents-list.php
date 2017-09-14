<?php
use app\models\UnpaidAnnualFee;
/* @var $model app\models\Patents */
/* @var $idx integer */

/* @var $box_type string */
/* @var $fee app\models\UnpaidAnnualFee 缴费信息 */

$box_type = 'box-default';
$fee = UnpaidAnnualFee::findOne(['patentAjxxbID' => $model->patentAjxxbID, 'due_date' => $model->patentFeeDueDate]);
if ($model->patentCaseStatus == '有效') {
    // 15天之内红色 90天之内黄色 其他绿色
    $diff_days = (int)date_diff(date_create(date('Ymd')),date_create($model->patentFeeDueDate))->format('%R%a');
    if ($diff_days < 0) {
        // TODO 过期
    } elseif ($diff_days == 0) {
        // TODO 今天到期
    } elseif ($diff_days <= 15) {
        $box_type = 'box-danger';
    } elseif ($diff_days <= 90) {
        $box_type = 'box-warning';
    } else {
        $box_type = 'box-success';
    }
} else {
    // TODO 非有效期
}
?>
<div class="box box-solid <?= $box_type ?>">
    <div class="box-header">
        <a href="javascript:void(0)" onclick="collapseToggle(<?= $idx ?>)" style="display: block">
<!--            <i class="fa fa-file-o"></i>-->
            <?= $idx ?> .
            <h3 class="box-title">
                <?= $model->patentTitle ?>
            </h3>
        </a>

        <div class="box-tools pull-right">
            <button id="<?= $idx ?>"  type="button" class="btn btn-default btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <a type="button" class="btn btn-default btn-sm" title="点击查看专利进度" href="<?= \yii\helpers\Url::to(['patents/main', 'id' => $model->patentAjxxbID]) ?>"><i class="fa fa-paper-plane"></i>
            </a>
        </div>
    </div>
    <div class="box-body" style="display: block">
        <dl>
            <dt>专利类型</dt>
            <dd><?= $model->patentType ?></dd>
            <dt>创建时间</dt>
            <dd><?= Yii::$app->formatter->asDatetime($model->UnixTimestamp / 1000) ?></dd>
<!--            <dt>主办人</dt>-->
<!--            <dd>--><?//= $model->patentAgent ? ($model->patentAgent . '(<a href="tel:' . $model->agentContact['userCellphone'] . '">' . $model->agentContact['userCellphone'] . '</a>)') : '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?><!--</dd>-->
            <dt>申请号</dt>
            <dd><?= $model->patentApplicationNo ?: '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?></dd>
            <dt>申请日</dt>
            <dd><?= $model->patentApplicationDate ?: '<span class="text-red" style="text-decoration: underline">暂未设置</span>' ?></dd>
        </dl>
        <?php
        if ($fee) {
            if ($this->context->isMicroMessage) {
                echo '<a class="btn btn-success btn-xs" id="pay-btn" data-id="' . $fee->patentAjxxbID . '">缴费('. $fee->fee_type . ':' . $fee->amount .'元)</a><div id="wxJS"></div>'; // TODO 如何给客户展示：颜色以及显示内容等等
            } else {
                echo '<a href="#" class="btn btn-success btn-xs" id="pay-btn" data-toggle="tooltip" data-html="true"  data-placement="right" title="<img src=\''.\yii\helpers\Url::to(["pay/wx-qrcode", "id"=>$fee->patentAjxxbID]).'\' />">缴费('. $fee->fee_type . ':' . $fee->amount .'元)</a>';
            }
        }
        ?>
    </div>
</div>
<?php
if ($this->context->isMicroMessage) {
    $this->registerJs('
$(\'#pay-btn\').click(function(){
	var url = "'. \yii\helpers\Url::to(["pay/payment"]).'";
        $.post(url, {pay_type:\'WXPAY\',id:$(this).data(\'id\')}, function(d) {
	    alert(1);
            if(d.done == true) {
                $(\'#wxJS\').html(d.data)
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
