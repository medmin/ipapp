<?php
/**
 * User: Mr-mao
 * Date: 2017/8/9
 * Time: 9:16
 */
use yii\helpers\Html;

/* @var $model \app\models\Notification */

$img_url = 'https://adminlte.io/themes/AdminLTE/dist/img/user7-128x128.jpg';
?>
<div class="box-comment">
    <img class="img-circle img-sm" src="<?= $img_url ?>" alt="User Image">

    <div class="comment-text">
          <span class="username">
            <?= Html::a($model->user->userUsername, ['/users/view', 'id' => $model->user->userID ]) ?>
            <span class="text-muted pull-right"><?= Yii::$app->formatter->asDatetime($model->createdAt) ?></span>
          </span>
        <?= Html::encode($model->content) ?>
    </div>
</div>
