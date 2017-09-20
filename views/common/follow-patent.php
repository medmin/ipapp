<?php
/**
 * User: Mr-mao
 * Date: 2017/9/20
 * Time: 16:24
 */

/* @var $model \app\models\Patents */

$fee = json_decode($model->generateUnpaidAnnualFee(), true);
?>
<div class="patent-info well well-sm no-shadow">
    <p>标题：<?= $model->patentTitle ?></p>
    <p>发明人：<?= $model->patentInventors ?></p>
    <p>申请号：<?= $model->patentApplicationNo ?></p>
    <p>缴费截止日：<?= substr_replace(substr_replace($model->patentFeeDueDate,'-',4,0),'-',-2,0) . ' ' . (($fee['status'] == false && $fee['msg'] == 'PAID') ? '（<span class="text-green">已缴费</span>）' : ($fee['status'] == true ? '（<span class="text-red">暂未缴费</span>）' : '（<span class="text-yellow">未查找到相关缴费信息</span>）')) ?></p>
    <div class="">
        <?= '<a href="javascript:;" onclick="unfollow(this)" class="btn btn-warning btn-sm btn-flat" data-id="' . $model->patentID . '">取消监管</a>'; ?>
        <?php
        if ($fee['status'] === true) {
            if ($this->context->isMicroMessage) {
                echo '<a href="javascript:;" id="pay-btn" class="btn btn-success btn-sm btn-flat" data-id="' . $model->patentAjxxbID . '">立即缴费(￥' . $fee['data']['amount'] . ')</a><div id="wxJS"></div>';
            } else {
                echo '<a href="#" id="pay-btn" class="btn btn-success btn-sm btn-flat" >立即缴费(￥' . $fee['data']['amount'] . ')</a>'; // TODO 非微信端展示（二维码） [*****]
            }
        }
        ?>
    </div>
</div>
