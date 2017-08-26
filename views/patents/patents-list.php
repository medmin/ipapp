<?php
/* @var $model app\models\Patents */
/* @var $idx integer */
?>
<div class="box box-solid box-default">
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
    </div>
</div>
