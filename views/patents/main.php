<?php
/* @var $models */
/* @var $model app\models\PatentEvents */
$this->title = Yii::t('app', 'Patents Progress');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="box box-primary">
    <div class="box-body">
        <?php
        if (!$models) {
            echo '该专利暂无动态';
        } else {
            echo $this->render('timeline', ['models' => $models]);
        }
        ?>
    </div>


</div>
