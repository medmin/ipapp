<?php
/* @var $this yii\web\View */
/* @var $models app\models\Notification */
/* @var $allModels \yii\data\ActiveDataProvider */
$this->title = Yii::t('app', 'Notify');
$this->params['breadcrumbs'][] = $this->title;
$is_pagination = Yii::$app->request->getQueryParam('page') === null; // 查看是否有分页，只有查看全部消息的时候才有分页信息，所以目前可以简单根据这个来判断active
?>
<div class="users-notify">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="<?= $is_pagination ? 'active' : ''?>">
                <a href="#newNotifies" data-toggle="tab" aria-expanded="true">未读消息</a>
            </li>
            <li class="<?= $is_pagination ? '' : 'active'?>">
                <a href="#allNotifies" data-toggle="tab" aria-expanded="true">所有消息</a>
            </li>
            <li class="box-tools pull-right">
                <a href="<?= \yii\helpers\Url::to(['notification/wechat-log'])?>" title="查看微信模板信息发送记录"><i class="fa fa-navicon"></i></a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane <?= $is_pagination ? 'active' : ''?>" id="newNotifies">
                <div class="box-footer box-comments">
                    <?php
                    if (!$models) {
                        echo '暂时没有未读消息';
                    } else {
                        foreach ($models as $model) {
                            echo $this->render('/notification/show', ['model' => $model]);
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="tab-pane <?= $is_pagination ? '' : 'active'?>" id="allNotifies">
                <div class="box-footer box-comments">
                    <?php
                    if (!($allModels->models)) {
                        echo '暂时没有任何消息';
                    } else {
                        foreach ($allModels->models as $one_model) {
                            echo $this->render('/notification/show', ['model' => $one_model]);
                        }
                        echo '<div class="box-comment" style="padding: 0">';
                        echo \yii\widgets\LinkPager::widget([
                            'pagination' => $allModels->pagination,
                            'options' => ['class' => 'pagination', 'style' => 'margin-bottom: 0;']
                        ]);
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
