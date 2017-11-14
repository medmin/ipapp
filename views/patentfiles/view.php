<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Patentfiles */

$this->title = '文件详情';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patentfiles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = $model->fileID;
?>
<div class="patentfiles-view">
    <div class="box box-info">
        <div class="box-header">
            <p>
                <?//= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->fileID], ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->fileID], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'fileID',
                    'patentAjxxbID',
                    'fileName',
    //            'filePath',
                    'fileUploadUserID',
                    'fileUploadedAt',
                    'fileUpdateUserID',
                    'fileUpdatedAt',
                    'fileNote',
                ],
            ]) ?>
        </div>
    </div>
</div>
