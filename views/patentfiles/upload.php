<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-19
 * Time: 15:43
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 * @var $model app\models\UploadForm
 */
?>
<?php
use yii\widgets\ActiveForm;
?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

<?= $form->field($model, 'patentFiles[]')->fileInput(['multiple' => true, 'accept' => '*']) ?>
<div>
    <p>注意：最多一次性上传5个文件，单个文件最大16M</p>
    <p>可以上传的文件后缀有：tiff, png, jpg, doc, docx, xls, xlsx, ppt, pptx, pdf, zip, rar, 7z, txt</p><br>
</div>

<button>上传文件</button>

<?php ActiveForm::end() ?>
