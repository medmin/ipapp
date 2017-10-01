<?php
/**
 * User: Mr-mao
 * Date: 2017/9/30
 * Time: 20:48
 */

/* @var $model \app\models\Patents */
?>
<div class="patent-info well well-sm no-shadow">
    <i class="fa fa-trash-o pull-right" data-id="<?= $model->patentID ?>" onclick="unfollow(this)" style="cursor: pointer;" title="取消监管"></i>
    <p>申请号：<?= $model->patentApplicationNo ?></p>
    <p>标题：<?= $model->patentTitle ?></p>
    <p>发明人：<?= $model->patentInventors ?></p>
    <?php
    $fee_info = $model->generateExpiredItems();
    $html = '';
    if (!count($fee_info)) {
        $html .= '<p>年费状态：<span class="label label-success">正常</span></p>'; // 没有查到年费
//        $html .= '<p>缴费截止日：</p>';
    } else {
        $tmp_array = ['description' => '即将到期', 'amount' => 0, 'color' => 'warning', 'status' => true];
        $detail_show = '<table>';
        foreach ($fee_info as $fee) {
            if ($fee['due_date'] < date('Ymd')) {
                $tmp_array['color'] = 'danger';
                $tmp_array['description'] = '已逾期</p>';
            }
            if ($fee['due_date'] < (date('Ymd', strtotime('-25 day')))) {
                $tmp_array['color'] = 'danger';
                $detail_show .= '<tr><td>'. $fee['fee_type'] .'</td><td style=\'padding-left: 8px\'>已逾期</td></tr>';
                $tmp_array['status'] = false;
            } else {
                $detail_show .= '<tr><td>'. $fee['fee_type'] .'</td><td style=\'padding-left: 8px\'>'. $fee['amount'] .'</td></tr>';
            }
            if ($fee['status'] == \app\models\UnpaidAnnualFee::PAID) {
                $tmp_array['description'] = '正在处理';
                $tmp_array['color'] = 'info';
                $tmp_array['status'] = false;
            }
            $tmp_array['amount'] += $fee['amount']; //总金额
        }
        $detail_show .= '</table>';

        $html .= '<p>年费状态：<span class="label label-'. $tmp_array['color'] .'" data-toggle="tooltip" data-placement="bottom" title="" data-html="true" data-original-title="'. $detail_show .'" >'. $tmp_array['description'] .'</span></p>';
        $html .= '<div>';
        if ($tmp_array['status']) {
            if ($this->context->isMicroMessage) {
                $html .= '<a href="javascript:;" id="pay-btn" class="pay-link" data-id="' . $model->patentAjxxbID . '">立即缴费(￥' . $tmp_array['amount'] . ')</a><div id="wxJS"></div>';
            } else {
                $html .= '<a href="javascript:;" id="pay-btn" class="pay-link" onclick="$(this).parent().children(\'.pay-qrcode\').toggle()">立即缴费(￥' . $tmp_array['amount'] . ')</a>';
                $html .= '<div class="clearfix"></div>';
                $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;padding: 10px;background: #2ead38;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用微信支付</span></p><img src="'. \yii\helpers\Url::to(["pay/wx-qrcode", "id"=>$model->patentAjxxbID]) .'" alt=""></div>';
//                $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;margin-left: 20px;padding: 10px;background: #00a2e3;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用支付宝支付</span></p><img src="'. \yii\helpers\Url::to(["pay/ali-qrcode", "id"=>$model->patentAjxxbID]) .'" alt=""></div>';
                $html .= '<div class="clearfix"></div>';
            }
        } else {
            if ($tmp_array['color'] == 'info') {
                // 不展示缴费按钮并且颜色为info样式的时候，说明支付成功,阳光惠远正在处理
                $html .= '';
            } else {
                $html .= '<a href="javascript:;" class="pay-link-disabled">缴费不可用(请联系客服)</a>';
            }
        }
        $html .= '</div>';
    }
    echo $html;
    ?>
</div>
