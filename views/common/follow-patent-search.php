<?php
/**
 * User: Mr-mao
 * Date: 2017/9/20
 * Time: 13:25
 */

/* @var $model \app\models\Patents */
?>
<div class="well well-sm no-shadow">
    <p>标题：<?= $model->patentTitle ?></p>
    <p>申请人：<?= $model->patentApplicationInstitution ?></p>
    <p>发明人：<?= $model->patentInventors ?></p>
    <p>申请号：<?= $model->patentApplicationNo ?></p>
    <div class="">
        <?php
        // TODO 数据查询优化,优先度靠后
        if (\app\models\AnnualFeeMonitors::findOne(['user_id' => Yii::$app->user->id, 'patent_id' => $model->patentID])) {
            echo '<a href="javascript:;" onclick="unfollow(this)" class="btn btn-warning btn-sm btn-flat" data-id="' . $model->patentID . '">取消监管</a>';
        } else {
            echo '<a href="javascript:;" onclick="follow(this)" class="btn btn-primary btn-sm btn-flat" data-id="' . $model->patentID . '">添加监管</a>';
        }
        ?>
    </div>
</div>
