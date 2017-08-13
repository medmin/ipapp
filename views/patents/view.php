<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Patents */

$this->title = $model->patentID;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Patents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="patents-view">
    <div class="box box-info">
        <? if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN) {
            $html = '<div class="box-header with-border"><p>';
            $html .= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->patentID], ['class' => 'btn btn-primary']);
            $html .= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->patentID], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]);
            $html .= '</p></div>';
            echo $html;
        }?>
        <div class="box-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'patentID',
                    'patentAjxxbID',
                    'patentEacCaseNo',
                    'patentType',
                    'patentUserID',
                    'patentUsername',
                    'patentUserLiaisonID',
                    'patentUserLiaison',
                    'patentAgent',
                    'patentProcessManager',
                    'patentTitle',
                    'patentApplicationNo',
                    'patentPatentNo',
                    'patentNote:ntext',
                    'UnixTimestamp:datetime',
                ],
            ]) ?>
        </div>
    </div>
</div>