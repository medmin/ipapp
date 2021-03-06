<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-19
 * Time: 15:43
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */

use yii\widgets\ActiveForm;

/* @var $model app\models\UploadForm */
?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'files-upload-form']) ?>


<?= $form->field($model, 'ajxxb_id')->hiddenInput(['value' => $model->ajxxb_id])->label(false) ?>

<?= $form->field($model, 'patentFiles[]')->fileInput(['multiple' => true, 'accept' => '*'/*, 'style' => 'display: none'*/])->label(false) ?>

<p class="text-muted well well-sm no-shadow">
    注意：一次性最多上传5个文件，一次性最大上传50M <br>
    可以上传的文件后缀有：tif, png, jpg, doc, docx, xls, xlsx, ppt, pptx, pdf, zip, rar, 7z, txt
</p>

<?= $form->field($model, 'eventType')->dropDownList($eventTypes) ?>

<div class="progress active" style="display: none">
    <div class="progress-bar progress-bar-success" id="files-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 00%">0%
    </div>
</div>

<div class="input-group">
    <button class="btn btn-info pull-right">确认上传</button>
</div>

<?php ActiveForm::end() ?>
