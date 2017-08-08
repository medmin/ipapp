<?php
/* @var $model \app\models\Patentevents */
/* @var $link bool */

?>
<li>
    <i class="fa fa-user fa-aqua"></i>
    <div class="timeline-item">
        <span class="time">
            <i class="fa fa-clock-o">
                <?= Yii::$app->formatter->asRelativeTime($model->eventCreatUnixTS / 1000) ?>
            </i>
        </span>
        <h3 class="timeline-header">
            <?= ($link ? \yii\helpers\Html::a($model->patentAjxxbID, ['/patents/main', 'id' => $model->patentAjxxbID]) : '') . $model->eventContent ?>
        </h3>
    </div>
</li>
