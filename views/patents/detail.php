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
            <?php
            $html = $link ? \yii\helpers\Html::a($model->patent->patentTitle, ['/patents/main', 'id' => $model->patentAjxxbID]) : '';
            $html .= ' ' . $model->eventContent;
            if ($model->eventContentID == 'file') {
                $file_id = substr($model->eventRwslID, strrpos($model->eventRwslID, '_') + 1 );
                $html .= '<span title="点击下载该文件" class=\'file-download\' onclick=\'download("'. $file_id .'")\'><i class="fa fa-download"></i></span>';
            }
            echo $html;
            ?>
        </h3>
    </div>
</li>
