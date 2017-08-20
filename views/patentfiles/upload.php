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

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

<?= $form->field($model, 'ajxxb_id')->hiddenInput(['value' => $model->ajxxb_id])->label(false) ?>

<?= $form->field($model, 'patentFiles[]')->fileInput(['multiple' => true, 'accept' => '*', 'style' => 'display: none'])->label(false) ?>

<p class="text-muted well well-sm no-shadow">
    注意：最多一次性上传5个文件，单个文件最大16M <br>
    可以上传的文件后缀有：tiff, png, jpg, doc, docx, xls, xlsx, ppt, pptx, pdf, zip, rar, 7z, txt
</p>

<div class="input-group col-md-12" onclick="$('input[id=uploadform-patentfiles]').click();">
    <input type="text" class="form-control" title="选择文件">
    <span class="input-group-btn">
      <button type="button" class="btn btn-default btn-flat">选择文件</button>
    </span>
    <button class="btn btn-info pull-right filesSubmit">确认上传</button>
</div>

<?php ActiveForm::end() ?>
