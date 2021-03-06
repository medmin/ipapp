<?php
/**
 * User: Mr-mao
 * Date: 2017/9/20
 * Time: 13:25
 */

/* @var $model \app\models\Patents */
?>
<div class="well well-sm no-shadow">
    <p>标题：<?= $patent['title'] ?></p>
    <p>申请人：<?= $patent['applicants'] ?></p>
    <p>发明人：<?= $patent['inventors'] ?></p>
    <p>申请号：<?= $patent['application_no'] ?></p>
    <div class="">
        <?php
        // TODO 数据查询优化,优先度靠后
        if (\app\models\AnnualFeeMonitors::findOne(['user_id' => Yii::$app->user->id, 'application_no' => $patent['application_no']])) {
            echo '<a href="javascript:;" onclick="unfollow(this)" class="btn btn-warning btn-sm btn-flat" data-application_no="' . $patent['application_no'] . '">取消监管</a>';
        } else {
            echo '<a href="javascript:;" onclick="follow(this)" class="btn btn-primary btn-sm btn-flat" data-application_no="' . $patent['application_no'] . '">添加监管</a>';
        }
        ?>
    </div>
</div>
