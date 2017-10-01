<?php
/**
 * User: Mr-mao
 * Date: 2017/9/20
 * Time: 16:24
 */

/* 页面重做至 follow-patent_2 */
/* @var $model \app\models\Patents */

$fee = json_decode($model->generateUnpaidAnnualFee(), true);
?>
<div class="patent-info well well-sm no-shadow">
    <i class="fa fa-trash-o pull-right" data-id="<?= $model->patentID ?>" onclick="unfollow(this)" style="cursor: pointer;" title="取消监管"></i>
    <p>申请号：<?= $model->patentApplicationNo ?></p>
    <p>标题：<?= $model->patentTitle ?></p>
    <p>发明人：<?= $model->patentInventors ?></p>
    <p>缴费截止日：<?= substr_replace(substr_replace($model->patentFeeDueDate,'-',4,0),'-',-2,0) . ' ' . (($fee['status'] == false && $fee['msg'] == 'PAID') ? '（<span class="text-green">已缴费</span>）' : ($fee['status'] == true ? '（<span class="text-red">暂未缴费</span>）' : '（<span class="text-yellow">未查找到相关缴费信息</span>）')) ?></p>
    <div class="">
        <?//= '<a href="javascript:;" onclick="unfollow(this)" class="btn btn-warning btn-sm btn-flat" data-id="' . $model->patentID . '">取消监管</a>'; ?>
        <?php
        if ($fee['status'] === true) {
            if ($this->context->isMicroMessage) {
                echo '<a href="javascript:;" id="pay-btn" class="btn btn-success btn-sm btn-flat" data-id="' . $model->patentAjxxbID . '">立即缴费(￥' . $fee['data']['amount'] . ')</a><div id="wxJS"></div>';
            } else {
                echo '<a href="#" id="pay-btn" class="btn btn-success btn-sm btn-flat" onclick="$(this).parent().children(\'.pay-qrcode\').toggle()">立即缴费(￥' . $fee['data']['amount'] . ')</a>';
                $qrcodeDiv = '<div class="clearfix"></div>';
                $qrcodeDiv .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;padding: 10px;background: #2ead38;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用微信支付</span></p><img src="'. \yii\helpers\Url::to(["pay/wx-qrcode", "id"=>$model->patentAjxxbID]) .'" alt=""></div>';
//                $qrcodeDiv .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;margin-left: 20px;padding: 10px;background: #00a2e3;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用支付宝支付</span></p><img src="'. \yii\helpers\Url::to(["pay/ali-qrcode", "id"=>$model->patentAjxxbID]) .'" alt=""></div>';
                $qrcodeDiv .= '<div class="clearfix"></div>';
                echo $qrcodeDiv;
            }
        }
        ?>
    </div>
</div>
