<?php
/* @var $model app\models\Patents */
?>
<div class="box box-solid">
    <div class="box-header">
        <i class="fa fa-file-o"></i>
        <h3 class="box-title">
            <?= $model->patentTitle?>
        </h3>
        <div class="box-tools pull-right">
<!--            <button type="button" class="btn btn-default btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>-->
<!--            </button>-->
            <button type="button" class="btn btn-default btn-sm"><a href="<?= \yii\helpers\Url::to(['patents/main', 'id' => $model->patentAjxxbID]) ?>" title="点击查看专利进度"><i class="fa fa-paper-plane"></i></a>
            </button>
        </div>
    </div>
    <div class="box-body" style="display: none">

    </div>
</div>
