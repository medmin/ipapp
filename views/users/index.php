<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\wechat\models\TemplateForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UsersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
$js = <<<JS
let template1 = `
<div class="form-group">
    <label for="template-keyword1" class="control-label">客户名称:</label>
    <input name="template[keyword1]" type="text" class="form-control" id="template-keyword1">
</div>
<div class="form-group">
    <label for="template-keyword2" class="control-label">客服类型:</label>
    <input name="template[keyword2]" type="text" class="form-control" id="template-keyword2">
</div>
<div class="form-group">
    <label for="template-keyword3" class="control-label">提醒内容:</label>
    <input name="template[keyword3]" type="text" class="form-control" id="template-keyword3">
</div>
<div class="form-group">
    <label for="template-keyword4" class="control-label">通知时间:</label>
    <input name="template[keyword4]" type="text" class="form-control" id="template-keyword4">
</div>
`;
let template2 = `
<div class="form-group">
    <label for="template-keyword1" class="control-label">待办内容:</label>
    <input name="template[keyword1]" type="text" class="form-control" id="template-keyword1">
</div>
<div class="form-group">
    <label for="template-keyword2" class="control-label">待办时间:</label>
    <input name="template[keyword2]" type="text" class="form-control" id="template-keyword2">
</div>
`;

$("#templates-select").change(function() {
  let type = $(this).children('option:selected').val();
  let append = $("#modal-append");
  switch(type)
  {
      case "WXrxhUrFslEmmVlnQqwCKI1kVbF6FsoIYoSg6aX4Cug":
        append.html(template1);
        break;
      case "j0VDfgYFGY9BJSjdyI8PjwuNMYHwgHpvKOIOMlX732w":
        append.html(template2);
        break;
      default :
        append.html(template1);
  }
});

$("#templates-submit").click(function() {
  let form = $("#templates-form");
  // console.log(form.attr("action"), form.serialize(),1,form)
  $.post(form.attr("action"), form.serialize(), function(data) {
    if (data['code'] === 0) {
       $("#wechatModal").modal("hide");
       form[0].reset();
    } else {
        alert(data['msg']);
    }
  }, 'json');
});

$('#wechatModal').on('hidden.bs.modal', function (e) {
  $("#templates-form")[0].reset();
  console.log('yin')
});

function toggleWechatModal(openid) {
  $("#template-openid").val(openid);
  $("#wechatModal").modal("show");
}
function searchToggle() {
  $("#toggleSearchBtn").trigger("click");
}
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
<div class="users-index">
    <div class="box box-default collapsed-box">
        <div class="box-header with-border">
            <a href="javascript:void(0)" onclick="searchToggle()" style="display: block;"><h3 class="box-title"><small>搜索</small></h3></a>

            <div class="box-tools pull-right">
                <button id="toggleSearchBtn" type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <?php  echo $this->render('_search', ['model' => $searchModel]); ?>
        </div>
    </div>
    <div class="box box-primary">
        <?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN) {
            echo '<div class="box-header with-border"><p>' . Html::a(Yii::t('app', 'Create Users'), ['create'], ['class' => 'btn btn-success']) . '</p></div>';
        }?>
        <div class="box-body table-responsive">
            <?php if (Yii::$app->user->identity->userRole == \app\models\Users::ROLE_EMPLOYEE): ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'userUsername',
                            'value' => function ($model) {
                                return Html::a($model->userUsername, \yii\helpers\Url::to(['view', 'id' => $model->userID]));
                            },
                            'format' => 'raw'
                        ],
//                        'userOrganization',
                        'userFullname',
                        'userEmail:email',
                        'userCellphone',
                        'userNote:ntext',
                    ]
                ]); ?>
            <?php else: ?>
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
                        'header' => Yii::t('app', 'Operation'),
                        'template' => '
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    操作
                                    <span class="fa fa-caret-down"></span>
                                </button>
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li>{view}</li> 
                                    '.((Yii::$app->user->identity->userRole == \app\models\Users::ROLE_ADMIN || Yii::$app->user->identity->userRole == \app\models\Users::DEMO) ? '
                                    <li>{update}</li>
                                    <li>{wechat}</li>
                                    ' : '').((Yii::$app->user->identity->userRole !== \app\models\Users::ROLE_CLIENT) ? '
                                    <li>{patents}</li>
                                    <li>{follow}<li>
                                    <li>{schedule}</li>
                                    ' : '').'
                                </ul>
                            </div>
                        ',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('查看', $url);
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a('更新', $url);
                            },
                            'wechat' => function ($url, $model, $key) {
                                return (isset($model->wxUser->fakeid) && !empty($model->wxUser->fakeid)) ? Html::a('微信通知', 'javascript:toggleWechatModal("'.$model->wxUser->fakeid.'")', ['id' => 'wechat']) : '';
                            },
                            'patents' => function ($url, $model, $key) {
                                return Html::a('专利', \yii\helpers\Url::to(['patents/index', 'PatentsSearch[patentUserID]' => $key]));
                            },
                            'schedule' => function ($url, $model, $key) {
                                return Html::a('主页', \yii\helpers\Url::to(['users/events-schedule', 'user_id' => $key]));
                            },
                            'follow' => function ($url, $model, $key) {
//                                return Html::a('监管专利', \yii\helpers\Url::to(['users/client-monitor-patents', 'user_id' => $key]));
                            }
                        ]
                    ],
                ],
            ]); ?>
            <?php endif; ?>
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
                <form action="<?= \yii\helpers\Url::to(['/wechat/wechat/send-template']) ?>" id="templates-form">
                    <input type="text" title="openid" value="" name="template[openid]" style="display: none;" id="template-openid">
                    <div class="form-group">
                        <label>选择模板类型</label>
                        <select title class="form-control" id="templates-select" name="template[name]">
                            <option value="<?= TemplateForm::CUSTOMER_ALERTS_NOTIFICATION ?>"><?= TemplateForm::status()[TemplateForm::CUSTOMER_ALERTS_NOTIFICATION] ?></option>
                            <option value="<?= TemplateForm::SCHEDULE ?>"><?= TemplateForm::status()[TemplateForm::SCHEDULE] ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="template-first" class="control-label">标题:</label>
                        <input name="template[first]" type="text" class="form-control" id="template-first">
                    </div>
                    <div id="modal-append">
                        <div class="form-group">
                            <label for="template-keyword1" class="control-label">客户名称:</label>
                            <input name="template[keyword1]" type="text" class="form-control" id="template-keyword1">
                        </div>
                        <div class="form-group">
                            <label for="template-keyword2" class="control-label">客服类型:</label>
                            <input name="template[keyword2]" type="text" class="form-control" id="template-keyword2">
                        </div>
                        <div class="form-group">
                            <label for="template-keyword3" class="control-label">提醒内容:</label>
                            <input name="template[keyword3]" type="text" class="form-control" id="template-keyword3">
                        </div>
                        <div class="form-group">
                            <label for="template-keyword4" class="control-label">通知时间:</label>
                            <input name="template[keyword4]" type="text" class="form-control" id="template-keyword4">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="template-remark" class="control-label">备注:</label>
                        <input name="template[remark]" type="text" class="form-control" id="template-remark">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="templates-submit">发送</button>
            </div>
        </div>
    </div>
</div>

