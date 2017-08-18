<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-index">
    <div class="box box-default">

        <?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN) {
            echo '<div class="box-header with-border"><p>' . Html::a(Yii::t('app', 'Create Users'), ['create'], ['class' => 'btn btn-success']) . '</p></div>';
        }?>
        <div class="box-body">
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>


            <?= GridView::widget([
                'dataProvider' => $dataProvider,
//                'filterModel' => $searchModel,
                'columns' => [
//                    ['class' => 'yii\grid\SerialColumn'],

                    'userID',
                    'userUsername',
//                    'userPassword',
                    'userOrganization',
                    'userFullname',
                    // 'userFirstname',
                    // 'userGivenname',
                    // 'userNationality',
                    // 'userCitizenID',
                     'userEmail:email',
                     'userCellphone',
//                     'userLandline',
//                     'userAddress',
                     'userLiaison',
                    // 'userLiaisonID',
//                     [
//                         'attribute' => 'userRole',
//                         'value' => function ($model) {
//                              if ($model->userRole == \app\models\Users::ROLE_ADMIN) {
//                                  $html = '<span class="text-red" style="text-decoration: underline">超级管理员</span>';
//                              } elseif ($model->userRole == \app\models\Users::ROLE_SECONDARY_ADMIN) {
//                                  $html = '<span class="text-green" style="text-decoration: underline">二级管理员</span>';
//                              } elseif ($model->userRole == \app\models\Users::ROLE_EMPLOYEE) {
//                                  $html = '<span class="text-blue" style="text-decoration: underline">商务专员</span>';
//                              } else {
//                                  $html = '<span>客户</span>';
//                              }
//                              return $html;
//                         },
//                         'format' => 'raw',
//                     ],
                     'userNote:ntext',
                    // 'authKey',
                    // 'UnixTimestamp:datetime',

                    [
                        'class' => 'yii\grid\ActionColumn',
//                        'header' => Yii::t('app', 'Operation'),
                        'template' => '
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    操作
                                    <span class="fa fa-caret-down"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li>{view}</li> 
                                    '.(Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN ? '
                                    <li>{update}</li>
                                    <li>{wechat}</li>
                                    ' : '').'
                                </ul>
                            </div>
                        ',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('查看', $url, ['target' => '_blank']);
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url, ['target' => '_blank']);
                            },
                            'wechat' => function ($url, $model, $key) {
                                return isset($model->wxUserinfo->openid) ? Html::a('微信通知', '#', ['id' => 'wechat', 'data-id' => $model->wxUserinfo->openid, 'data-toggle' => "modal", 'data-target' => "#wechatModal"]) : '';
                            }
                        ]
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
<div class="modal fade" id="wechatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">发送微信模板消息</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="template-first" class="control-label">模板标题:</label>
                    <input type="text" class="form-control" id="template-first">
                </div>
                <div class="form-group">
                    <label for="template-keyword1" class="control-label">客户名:</label>
                    <input type="text" class="form-control" id="template-keyword1">
                </div>
                <div class="form-group">
                    <label for="template-keyword2" class="control-label">订单信息:</label>
                    <input type="text" class="form-control" id="template-keyword2">
                </div>
                <div class="form-group">
                    <label for="template-remark" class="control-label">模板备注:</label>
                    <input type="text" class="form-control" id="template-remark">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

