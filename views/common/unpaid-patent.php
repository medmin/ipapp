<?php
/**
 * User: Mr-mao
 * Date: 2017/10/6
 * Time: 21:13
 */

/* @var $model \app\models\Patents */
$fee_info = $model->generateExpiredItems(90,false);
?>
<?php if (!empty($fee_info)): ?>
<div class="patent-info well well-sm no-shadow">
    <i class="fa fa-trash-o pull-right" data-id="<?= $model->patentID ?>" onclick="unfollow(this)" style="cursor: pointer;" title="取消监管"></i>
    <p>申请号：<?= $model->patentApplicationNo ?></p>
    <p>标题：<?= $model->patentTitle ?></p>
    <p>发明人：<?= $model->patentInventors ?></p>
    <?php
    $html = '';
    $tmp_array = ['description' => '即将到期', 'amount' => 0, 'color' => 'warning', 'status' => true];
    $detail_show = '<table>';
    foreach ($fee_info as $unpaid_fee) {
        if ($unpaid_fee['due_date'] < (date('Ymd', strtotime('-25 day')))) {
            // 只要判断到有一条信息超过25天，就不展示支付按钮
            $tmp_array['color'] = 'danger';
            $tmp_array['status'] = false;
            $detail_show .= '<tr><td>'. $unpaid_fee['fee_type'] .'</td><td style=\'padding-left: 8px\'>已逾期</td></tr>';
        } else {
            $detail_show .= '<tr><td>'. $unpaid_fee['fee_type'] .'</td><td style=\'padding-left: 8px\'>'. $unpaid_fee['amount'] .'</td></tr>';
            $tmp_array['amount'] += $unpaid_fee['amount']; // 计算出总金额
        }
        if ($unpaid_fee['due_date'] < date('Ymd')) {
            // 只要有一条信息逾期了，就变红
            $tmp_array['color'] = 'danger';
            $tmp_array['description'] = '已逾期';
        }
    }
    $detail_show .= '</table>';
    $html .= '<p>年费状态：<span class="label label-'. $tmp_array['color'] .'" data-toggle="tooltip" data-placement="bottom" title="" data-html="true" data-original-title="'. $detail_show .'" >'. $tmp_array['description'] . '(点击查看明细)' .'</span></p>';
    $html .= '<div>';
    if ($tmp_array['status']) {
        if ($this->context->isMicroMessage) {
            $html .= '<a href="javascript:;" id="pay-btn" class="pay-link" data-id="' . $model->patentAjxxbID . '">立即缴费(￥' . $tmp_array['amount'] . ')</a><div id="wxJS"></div>';
        } else {
            $html .= '<a href="javascript:;" id="pay-btn" class="pay-link btn btn-success btn-flat" data-id="'. $model->patentAjxxbID .'">立即缴费(￥' . $tmp_array['amount'] . ')</a>';
            $html .= '<div class="clearfix"></div>';
            $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;padding: 10px;background: #2ead38;"></div>';
//                $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;margin-left: 20px;padding: 10px;background: #00a2e3;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用支付宝支付</span></p><img src="'. \yii\helpers\Url::to(["pay/ali-qrcode", "id"=>$model->patentAjxxbID]) .'" alt=""></div>';
            $html .= '<div class="clearfix"></div>';
        }
    } else {
        $html .= '<a href="javascript:;" class="pay-link-disabled">已逾期(请联系客服)</a>';
    }
    $html .= '</div>';
    echo $html;
    ?>
</div>
<?php endif; ?>
