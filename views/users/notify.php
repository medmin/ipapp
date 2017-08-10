<?php
/* @var $this yii\web\View */
/* @var $models app\models\Notification */
/* @var $allModels \yii\data\ActiveDataProvider */
$this->title = Yii::t('app', 'Notify');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-notify">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#newNotifies" data-toggle="tab" aria-expanded="true">未读消息</a>
            </li>
            <li class="">
                <a href="#allNotifies" data-toggle="tab" aria-expanded="true">所有消息</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="newNotifies">
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
            <div class="tab-pane" id="allNotifies">
                <div class="box-footer box-comments">
                    <?php
                    if (!($allModels->models)) {
                        echo '暂时没有任何消息';
                    } else {
                        foreach ($allModels->models as $one_model) {
                            echo $this->render('/notification/show', ['model' => $one_model]);
                        }
                        // 分页显示问题稍后处理 TODO
                        echo \yii\widgets\LinkPager::widget([
                            'pagination' => $allModels->pagination
                        ]);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
