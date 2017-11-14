<?php
/**
 * User: Mr-mao
 * Date: 2017/9/30
 * Time: 20:48
 */

/* @var $model \app\models\Patents */
?>
<div class="patent-info well well-sm no-shadow">
    <i class="fa fa-trash-o pull-right" data-application_no="<?= $patent['application_no'] ?>" onclick="unfollow(this)" style="cursor: pointer;" title="取消监管"></i>
    <p>申请号：<?= $patent['application_no'] ?></p>
    <p>标题：<?= $patent['title'] ?></p>
    <p>申请人：<?= $patent['applicants'] ?></p>
    <p>发明人：<?= $patent['inventors'] ?></p>
    <p>状态信息：<span class="label" style='background-color: <?= $patent['general_status_color'] ?> !important;'><?= $patent['general_status'] ?></span></p>
    <?php
    // $fee_info = [];
    // $model = \app\models\Patents::find()
    //     ->where(['patentAjxxbID' => 'AJ151100_1100'])
    //     ->one();
    // $fee_info = $model->generateExpiredItems();
    $fee_info = !empty($patent['fee_info']) ? $patent['fee_info'] : [];
    $html = '';
    if (!count($fee_info)) {
        $html .= '<p>年费状态：<span class="label label-success">正常</span></p>'; // 没有查到年费
//        $html .= '<p>缴费截止日：</p>';
    } else {
        $tmp_array = ['description' => '即将到期', 'amount' => 0, 'color' => 'warning', 'status' => true];
        $detail_show = '<table>';
        if (!empty($fee_info)) {
            $detail_show .= '<tr><td>'. $fee_info[0]['type'] .'</td><td style=\'padding-left: 8px\'>'. $fee_info[0]['amount'] .'</td></tr>';
//            foreach ($fee_info as $unpaid_fee) {
//                if (strtotime($unpaid_fee['due_date']) < strtotime((date('Ymd', strtotime('-25 day'))))) {
//                    // 只要判断到有一条信息超过25天，就不展示支付按钮
//                    $tmp_array['color'] = 'danger';
//                    $tmp_array['status'] = false;
//                    $detail_show .= '<tr><td>'. $unpaid_fee['type'] .'</td><td style=\'padding-left: 8px\'>已逾期</td></tr>';
//                } else {
//                    $detail_show .= '<tr><td>'. $unpaid_fee['type'] .'</td><td style=\'padding-left: 8px\'>'. $unpaid_fee['amount'] .'</td></tr>';
//                    $tmp_array['amount'] += $unpaid_fee['amount']; // 计算出总金额
//                }
//                if (strtotime($unpaid_fee['due_date']) < strtotime(date('Ymd'))) {
//                    // 只要有一条信息逾期了，就变红
//                    $tmp_array['color'] = 'danger';
//                    $tmp_array['description'] = '已逾期';
//                }
//            }
        }
        $detail_show .= '</table>';
        $html .= '<p>年费状态：<span class="label label-'. $tmp_array['color'] .'" data-toggle="tooltip" data-placement="bottom" title="" data-html="true" data-original-title="'. $detail_show .'" >'. $tmp_array['description'] .'</span>';
        $html .= '<div>';
        if ($tmp_array['status']) {
            if ($this->context->isMicroMessage) {
                $html .= '<a href="javascript:;" id="pay-btn" class="c_c" data-application_no="' . $patent['application_no'] . '">查看未缴年费</a>';
            } else {
                $html .= '<a href="javascript:;" id="pay-btn" class="c_c" data-application_no="'. $patent['application_no'] .'">查看未缴费用</a>';
                $html .= '<div class="clearfix"></div>';
                $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;padding: 10px;background: #2ead38;"></div>';
//                $html .= '<div class="pay-qrcode pull-left" style="display: none;margin-top: 20px;margin-left: 20px;padding: 10px;background: #00a2e3;width: 30%;"><p style="text-align: center"><span class="badge" style="background: #fff;color: #113521">使用支付宝支付</span></p><img src="'. \yii\helpers\Url::to(["pay/ali-qrcode", "id"=>$patent['application_no']]) .'" alt=""></div>';
                $html .= '<div class="clearfix"></div>';
            }
        }
        $html .= '</div>';
    }
    echo $html;
    ?>
</div>
